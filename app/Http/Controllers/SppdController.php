<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrSppd;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use App\Traits\HasDynamicFilter;
use App\Helpers\ApprovalHelper;

class SppdController extends Controller
{
    use HasDynamicFilter;
    /**
     * LIST SPPD
     */

    #[OA\Get(
        path: "/api/sppd",
        tags: ["SPPD"],
        summary: "List SPPD",
        security: [["bearerAuth" => []]],
        parameters: [

            new OA\Parameter(
                name: "search",
                in: "query",
                required: false,
                description: "Search keyword",
                schema: new OA\Schema(
                    type: "string",
                    example: "Meeting Jakarta"
                )
            ),

            new OA\Parameter(
                name: "approval_status",
                in: "query",
                required: false,
                description: "Filter approval status",
                schema: new OA\Schema(
                    type: "string",
                    example: "submitted",
                    enum: ["draft","submitted","approved","rejected","revision","cancelled","completed"]
                )
            ),

            new OA\Parameter(
                name: "approval_flow_id",
                in: "query",
                required: false,
                description: "Filter approval flow",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            ),

            new OA\Parameter(
                name: "sort_by",
                in: "query",
                required: false,
                description: "Sort field",
                schema: new OA\Schema(
                    type: "string",
                    example: "created_at"
                )
            ),

            new OA\Parameter(
                name: "sort_order",
                in: "query",
                required: false,
                description: "Sort order",
                schema: new OA\Schema(
                    type: "string",
                    enum: ["asc", "desc"],
                    example: "desc"
                )
            ),

            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                description: "Pagination per page",
                schema: new OA\Schema(
                    type: "integer",
                    example: 10
                )
            ),
        ],

        responses: [

            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    properties: [

                        new OA\Property(
                            property: "status",
                            type: "boolean",
                            example: true
                        ),

                        new OA\Property(
                            property: "data",
                            type: "object"
                        ),
                    ]
                )
            )
        ]
    )]
    
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = TrSppd::with([
            'requester',
            'peserta',
            'peserta.transportasi',
            'peserta.penginapan'
        ]);

        /*
        |--------------------------------------------------------------------------
        | DIVISION SCOPE
        |--------------------------------------------------------------------------
        */

        if (!$user->hasPermission('sppd.view.all')) {

            $query->whereHas('requester', function ($q) use ($user) {
                $q->where('division_id', $user->division_id);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | DYNAMIC FILTER
        |--------------------------------------------------------------------------
        */

        $query = $this->applyFilters(
            query: $query,
            request: $request,

            allowedFilters: [
                'approval_status',
                'approval_flow_id',
                'jenis_dokumen',
                'cost_center',
                'requester_id',
            ],

            searchableFields: [
                'jenis_dokumen',
                'cost_center',
                'kegiatan',
                'ringkasan_agenda',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | SORTING (optional tapi bagus)
        |--------------------------------------------------------------------------
        */
        $query->latest('id');

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        $paginated = $query->paginate(
            $request->get('per_page', 10)
        );

        /*
        |--------------------------------------------------------------------------
        | FORMAT COLLECTION (clean response)
        |--------------------------------------------------------------------------
        */

        $collection = $paginated->getCollection()->map(function ($item) {

            return [
                'id' => $item->id,
                'sppd_number' => $item->sppd_number,
                'jenis_dokumen' => $item->jenis_dokumen,
                'approval_flow_id' => $item->approval_flow_id,
                'cost_center' => $item->cost_center,
                'kegiatan' => $item->kegiatan,
                'ringkasan_agenda' => $item->ringkasan_agenda,
                'approval_status' => $item->approval_status,
                'grand_total' => $item->grand_total,
                'created_at' => $item->created_at,

                'requester' => $item->requester ? [
                    'id' => $item->requester->id,
                    'name' => encrypt_decrypt_db('dec', $item->requester->name, $item->requester->id),
                ] : null,

                'peserta' => $item->peserta->map(function ($p) {

                    return [
                        'id' => $p->id,
                        'nama' => $p->nama,
                        'nip' => $p->nip,
                        'jabatan' => $p->jabatan,
                        'kota_asal' => $p->kota_asal,
                        'kota_tujuan' => $p->kota_tujuan,
                        'dari_tanggal' => $p->dari_tanggal,
                        'sampai_tanggal' => $p->sampai_tanggal,

                        'transportasi' => $p->transportasi ?? null,
                        'penginapan' => $p->penginapan ?? null,
                    ];
                }),
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => true,

            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],

            'data' => $collection
        ]);
    }
    /**
     * DETAIL SPPD
     */

    #[OA\Get(
        path: "/api/sppd/{id}",
        tags: ["SPPD"],
        summary: "Detail SPPD",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],

        responses: [

            new OA\Response(
                response: 200,
                description: "Success"
            ),

            new OA\Response(
                response: 404,
                description: "SPPD not found"
            ),
        ]
    )]

    public function show($id)
    {
        $user = auth()->user();

        $data = TrSppd::with([
            'requester',
            'peserta.transportasi',
            'peserta.penginapan'
        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | CHECK DIVISION ACCESS
        |--------------------------------------------------------------------------
        */

        if (
            !$user->hasPermission('sppd.view.all')
            &&
            $data->requester?->division_id != $user->division_id
        ) {

            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki akses'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * CREATE SPPD
     */

    #[OA\Post(
        path: "/api/sppd",
        tags: ["SPPD"],
        summary: "Create SPPD",
        security: [["bearerAuth" => []]],

        requestBody: new OA\RequestBody(
            required: true,

            content: new OA\JsonContent(

                required: [
                    "jenisDokumen",
                    "costCenter",
                    "approvalFlowId",
                    "kegiatan",
                    "peserta"
                ],

                properties: [

                    /*
                    |--------------------------------------------------------------------------
                    | HEADER
                    |--------------------------------------------------------------------------
                    */

                    new OA\Property(
                        property: "jenisDokumen",
                        type: "bigint",
                        example: "1"
                    ),

                    new OA\Property(
                        property: "costCenter",
                        type: "string",
                        example: "CC001"
                    ),

                    new OA\Property(
                        property: "approvalFlowId",
                        type: "bigint",
                        example: "1"
                    ),

                    new OA\Property(
                        property: "kegiatan",
                        type: "string",
                        example: "Meeting Client"
                    ),

                    new OA\Property(
                        property: "ringkasanAgenda",
                        type: "string",
                        example: "Pembahasan kerjasama project"
                    ),

                    /*
                    |--------------------------------------------------------------------------
                    | PESERTA
                    |--------------------------------------------------------------------------
                    */

                    new OA\Property(
                        property: "peserta",
                        type: "array",

                        items: new OA\Items(

                            properties: [

                                new OA\Property(
                                    property: "nama",
                                    type: "string",
                                    example: "Bangkit"
                                ),

                                new OA\Property(
                                    property: "nip",
                                    type: "string",
                                    example: "123456789"
                                ),

                                new OA\Property(
                                    property: "jabatan",
                                    type: "string",
                                    example: "Backend Developer"
                                ),

                                new OA\Property(
                                    property: "kotaAsal",
                                    type: "string",
                                    example: "Jakarta"
                                ),

                                new OA\Property(
                                    property: "kotaTujuan",
                                    type: "string",
                                    example: "Bandung"
                                ),

                                new OA\Property(
                                    property: "dariTanggal",
                                    type: "string",
                                    format: "date"
                                ),

                                new OA\Property(
                                    property: "sampaiTanggal",
                                    type: "string",
                                    format: "date"
                                ),

                                /*
                                |--------------------------------------------------------------------------
                                | TRANSPORTASI
                                |--------------------------------------------------------------------------
                                */

                                new OA\Property(
                                    property: "transportasi",
                                    type: "array",

                                    items: new OA\Items(

                                        properties: [

                                            new OA\Property(
                                                property: "jenisTransportasi",
                                                type: "string",
                                                example: "Pesawat"
                                            ),

                                            new OA\Property(
                                                property: "namaTravel",
                                                type: "string",
                                                example: "Garuda Indonesia"
                                            ),

                                            new OA\Property(
                                                property: "asalKeberangkatan",
                                                type: "string",
                                                example: "Jakarta"
                                            ),

                                            new OA\Property(
                                                property: "tujuanKeberangakatan",
                                                type: "string",
                                                example: "Bandung"
                                            ),

                                            new OA\Property(
                                                property: "waktu",
                                                type: "string",
                                                format: "date-time"
                                            ),

                                            new OA\Property(
                                                property: "estimasiBiaya",
                                                type: "number",
                                                example: 1500000
                                            ),

                                            new OA\Property(
                                                property: "keterangan",
                                                type: "string",
                                                example: "PP"
                                            ),

                                            new OA\Property(
                                                property: "namaLengkap",
                                                type: "string",
                                                example: "Bangkit"
                                            ),

                                            new OA\Property(
                                                property: "noHp",
                                                type: "string",
                                                example: "08123456789"
                                            ),
                                        ]
                                    )
                                ),

                                /*
                                |--------------------------------------------------------------------------
                                | PENGINAPAN
                                |--------------------------------------------------------------------------
                                */

                                new OA\Property(
                                    property: "penginapan",
                                    type: "array",

                                    items: new OA\Items(

                                        properties: [

                                            new OA\Property(
                                                property: "jenisPenginapan",
                                                type: "string",
                                                example: "Hotel"
                                            ),

                                            new OA\Property(
                                                property: "namaTempat",
                                                type: "string",
                                                example: "Hotel Santika"
                                            ),

                                            new OA\Property(
                                                property: "lokasi",
                                                type: "string",
                                                example: "Bandung"
                                            ),

                                            new OA\Property(
                                                property: "checkIn",
                                                type: "string",
                                                format: "date"
                                            ),

                                            new OA\Property(
                                                property: "checkOut",
                                                type: "string",
                                                format: "date"
                                            ),

                                            new OA\Property(
                                                property: "estimasiBiaya",
                                                type: "number",
                                                example: 2500000
                                            ),

                                            new OA\Property(
                                                property: "keterangan",
                                                type: "string",
                                                example: "2 malam"
                                            ),

                                            new OA\Property(
                                                property: "namaLengkap",
                                                type: "string",
                                                example: "Bangkit"
                                            ),

                                            new OA\Property(
                                                property: "noHp",
                                                type: "string",
                                                example: "08123456789"
                                            ),
                                        ]
                                    )
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),

        responses: [

            new OA\Response(
                response: 200,
                description: "SPPD created successfully",

                content: new OA\JsonContent(

                    properties: [

                        new OA\Property(
                            property: "status",
                            type: "boolean",
                            example: true
                        ),

                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "SPPD created successfully"
                        ),

                        new OA\Property(
                            property: "data",
                            type: "object"
                        ),
                    ]
                )
            ),

            new OA\Response(
                response: 500,
                description: "Internal server error"
            )
        ]
    )]
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

        $sppdNumber = 'SPPD-' . date('YmdHis');
            $sppd = TrSppd::create([
                'sppd_number' => $sppdNumber,
                'jenis_dokumen' => $request->jenisDokumen,
                'cost_center' => $request->costCenter,
                'approval_flow_id' => $request->jenisPerjalanan,
                'kegiatan' => $request->kegiatan,
                'ringkasan_agenda' => $request->ringkasanAgenda,
                'requester_id' => auth()->id(),
                'approval_status' => 'draft'
            ]);

            $totalGrand = 0;

            foreach ($request->peserta as $p) {

                $peserta = $sppd->peserta()->create([
                    'nama' => $p['nama'],
                    'nip' => $p['nip'],
                    'jabatan' => $p['jabatan'],
                    'kota_asal' => $p['kotaAsal'],
                    'kota_tujuan' => $p['kotaTujuan'],
                    'dari_tanggal' => $p['dariTanggal'],
                    'sampai_tanggal' => $p['sampaiTanggal'],
                ]);

                $totalTransport = 0;
                $totalPenginapan = 0;

                /*
                |--------------------------------------------------------------------------
                | TRANSPORT
                |--------------------------------------------------------------------------
                */

                foreach ($p['transportasi'] ?? [] as $t) {

                    $peserta->transportasi()->create([
                        'jenis_transportasi' => $t['jenisTransportasi'] ?? null,
                        'nama_travel' => $t['namaTravel'] ?? null,
                        'asal_keberangkatan' => $t['asalKeberangkatan'] ?? null,
                        'tujuan_keberangkatan' => $t['tujuanKeberangakatan'] ?? null,
                        'waktu' => $t['waktu'] ?? null,
                        'estimasi_biaya' => $t['estimasiBiaya'] ?? 0,
                        'keterangan' => $t['keterangan'] ?? null,
                        'nama_lengkap' => $t['namaLengkap'] ?? null,
                        'no_hp' => $t['noHp'] ?? null,
                    ]);

                    $totalTransport += $t['estimasiBiaya'] ?? 0;
                }

                /*
                |--------------------------------------------------------------------------
                | PENGINAPAN
                |--------------------------------------------------------------------------
                */

                foreach ($p['penginapan'] ?? [] as $h) {

                    $peserta->penginapan()->create([
                        'jenis_penginapan' => $h['jenisPenginapan'] ?? null,
                        'nama_tempat' => $h['namaTempat'] ?? null,
                        'lokasi' => $h['lokasi'] ?? null,
                        'check_in' => $h['checkIn'] ?? null,
                        'check_out' => $h['checkOut'] ?? null,
                        'estimasi_biaya' => $h['estimasiBiaya'] ?? 0,
                        'keterangan' => $h['keterangan'] ?? null,
                        'nama_lengkap' => $h['namaLengkap'] ?? null,
                        'no_hp' => $h['noHp'] ?? null,
                    ]);

                    $totalPenginapan += $h['estimasiBiaya'] ?? 0;
                }

                $totalPeserta = $totalTransport + $totalPenginapan;

                $peserta->update([
                    'total_transport' => $totalTransport,
                    'total_accommodation' => $totalPenginapan,
                    'total_estimation' => $totalPeserta,
                ]);

                $totalGrand += $totalPeserta;
            }

            $sppd->update([
                'grand_total' => $totalGrand
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'SPPD created successfully',
                'data' => $sppd->load('peserta')
            ]);

        } catch (\Exception $e) {

            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * UPDATE SPPD
     */

    #[OA\Put(
        path: "/api/sppd/{id}",
        tags: ["SPPD"],
        summary: "Update SPPD",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "SPPD ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["jenisDokumen", "jenisPerjalanan", "peserta"],
                properties: [

                    // HEADER
                    new OA\Property(
                        property: "jenisDokumen",
                        type: "string",
                        example: "SPPD Direksi"
                    ),

                    new OA\Property(
                        property: "costCenter",
                        type: "string",
                        example: "CC001"
                    ),

                    new OA\Property(
                        property: "approvalFlowId",
                        type: "bigint",
                        example: "1"
                    ),

                    new OA\Property(
                        property: "kegiatan",
                        type: "string",
                        example: "Meeting Client"
                    ),

                    new OA\Property(
                        property: "ringkasanAgenda",
                        type: "string",
                        example: "Meeting kerja sama"
                    ),

                    // PESERTA
                    new OA\Property(
                        property: "peserta",
                        type: "array",
                        items: new OA\Items(
                            properties: [

                                new OA\Property(
                                    property: "nama",
                                    type: "string",
                                    example: "Bangkit"
                                ),

                                new OA\Property(
                                    property: "nip",
                                    type: "string",
                                    example: "123456"
                                ),

                                new OA\Property(
                                    property: "jabatan",
                                    type: "string",
                                    example: "Backend Developer"
                                ),

                                new OA\Property(
                                    property: "kotaAsal",
                                    type: "string",
                                    example: "Jakarta"
                                ),

                                new OA\Property(
                                    property: "kotaTujuan",
                                    type: "string",
                                    example: "Bandung"
                                ),

                                new OA\Property(
                                    property: "dariTanggal",
                                    type: "string",
                                    format: "date"
                                ),

                                new OA\Property(
                                    property: "sampaiTanggal",
                                    type: "string",
                                    format: "date"
                                ),

                                // TRANSPORT
                                new OA\Property(
                                    property: "transportasi",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [

                                            new OA\Property(
                                                property: "jenisTransportasi",
                                                type: "string",
                                                example: "Pesawat"
                                            ),

                                            new OA\Property(
                                                property: "namaTravel",
                                                type: "string",
                                                example: "Garuda"
                                            ),

                                            new OA\Property(
                                                property: "asalKeberangkatan",
                                                type: "string",
                                                example: "Jakarta"
                                            ),

                                            new OA\Property(
                                                property: "tujuanKeberangakatan",
                                                type: "string",
                                                example: "Bandung"
                                            ),

                                            new OA\Property(
                                                property: "waktu",
                                                type: "string",
                                                example: "2026-05-21 08:00:00"
                                            ),

                                            new OA\Property(
                                                property: "estimasiBiaya",
                                                type: "number",
                                                format: "float",
                                                example: 1500000
                                            ),

                                            new OA\Property(
                                                property: "keterangan",
                                                type: "string",
                                                example: "PP"
                                            ),

                                            new OA\Property(
                                                property: "namaLengkap",
                                                type: "string",
                                                example: "Bangkit"
                                            ),

                                            new OA\Property(
                                                property: "noHp",
                                                type: "string",
                                                example: "08123456789"
                                            ),
                                        ]
                                    )
                                ),

                                // PENGINAPAN
                                new OA\Property(
                                    property: "penginapan",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [

                                            new OA\Property(
                                                property: "jenisPenginapan",
                                                type: "string",
                                                example: "Hotel"
                                            ),

                                            new OA\Property(
                                                property: "namaTempat",
                                                type: "string",
                                                example: "Hilton"
                                            ),

                                            new OA\Property(
                                                property: "lokasi",
                                                type: "string",
                                                example: "Bandung"
                                            ),

                                            new OA\Property(
                                                property: "checkIn",
                                                type: "string",
                                                format: "date"
                                            ),

                                            new OA\Property(
                                                property: "checkOut",
                                                type: "string",
                                                format: "date"
                                            ),

                                            new OA\Property(
                                                property: "estimasiBiaya",
                                                type: "number",
                                                format: "float",
                                                example: 2500000
                                            ),

                                            new OA\Property(
                                                property: "keterangan",
                                                type: "string",
                                                example: "2 malam"
                                            ),

                                            new OA\Property(
                                                property: "namaLengkap",
                                                type: "string",
                                                example: "Bangkit"
                                            ),

                                            new OA\Property(
                                                property: "noHp",
                                                type: "string",
                                                example: "08123456789"
                                            ),
                                        ]
                                    )
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [

            new OA\Response(
                response: 200,
                description: "SPPD updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "status",
                            type: "boolean",
                            example: true
                        ),

                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "SPPD updated successfully"
                        ),
                    ]
                )
            ),

            new OA\Response(
                response: 403,
                description: "Forbidden"
            ),

            new OA\Response(
                response: 404,
                description: "Data not found"
            ),
        ]
    )]

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $user = auth()->user();

            $sppd = TrSppd::with([
                'requester',
                'peserta.transportasi',
                'peserta.penginapan'
            ])->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | DIVISION ACCESS
            |--------------------------------------------------------------------------
            */

            if (
                !$user->hasPermission('sppd.update.all')
                &&
                $sppd->requester?->division_id != $user->division_id
            ) {

                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses division'
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | ONLY DRAFT
            |--------------------------------------------------------------------------
            */

            if (
                !$user->hasPermission('sppd.update.all')
                &&
                $sppd->status !== 'draft'
            ) {

                return response()->json([
                    'status' => false,
                    'message' => 'SPPD hanya bisa diedit saat draft'
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE HEADER
            |--------------------------------------------------------------------------
            */

            $sppd->update([
                'jenis_dokumen' => $request->jenisDokumen,
                'cost_center' => $request->costCenter,
                'approval_flow_id' => $request->jenisPerjalanan,
                'kegiatan' => $request->kegiatan,
                'ringkasan_agenda' => $request->ringkasanAgenda,
            ]);

            /*
            |--------------------------------------------------------------------------
            | DELETE OLD DATA
            |--------------------------------------------------------------------------
            */

            foreach ($sppd->peserta as $pesertaOld) {

                $pesertaOld->transportasi()->delete();
                $pesertaOld->penginapan()->delete();
            }

            $sppd->peserta()->delete();

            /*
            |--------------------------------------------------------------------------
            | RECREATE
            |--------------------------------------------------------------------------
            */

            $totalGrand = 0;

            foreach ($request->peserta as $p) {

                $peserta = $sppd->peserta()->create([
                    'nama' => $p['nama'],
                    'nip' => $p['nip'],
                    'jabatan' => $p['jabatan'],
                    'kota_asal' => $p['kotaAsal'],
                    'kota_tujuan' => $p['kotaTujuan'],
                    'dari_tanggal' => $p['dariTanggal'],
                    'sampai_tanggal' => $p['sampaiTanggal'],
                ]);

                $totalTransport = 0;
                $totalPenginapan = 0;

                foreach ($p['transportasi'] ?? [] as $t) {

                    $peserta->transportasi()->create([
                        'jenis_transportasi' => $t['jenisTransportasi'] ?? null,
                        'nama_travel' => $t['namaTravel'] ?? null,
                        'asal_keberangkatan' => $t['asalKeberangkatan'] ?? null,
                        'tujuan_keberangkatan' => $t['tujuanKeberangakatan'] ?? null,
                        'waktu' => $t['waktu'] ?? null,
                        'estimasi_biaya' => $t['estimasiBiaya'] ?? 0,
                        'keterangan' => $t['keterangan'] ?? null,
                        'nama_lengkap' => $t['namaLengkap'] ?? null,
                        'no_hp' => $t['noHp'] ?? null,
                    ]);

                    $totalTransport += $t['estimasiBiaya'] ?? 0;
                }

                foreach ($p['penginapan'] ?? [] as $h) {

                    $peserta->penginapan()->create([
                        'jenis_penginapan' => $h['jenisPenginapan'] ?? null,
                        'nama_tempat' => $h['namaTempat'] ?? null,
                        'lokasi' => $h['lokasi'] ?? null,
                        'check_in' => $h['checkIn'] ?? null,
                        'check_out' => $h['checkOut'] ?? null,
                        'estimasi_biaya' => $h['estimasiBiaya'] ?? 0,
                        'keterangan' => $h['keterangan'] ?? null,
                        'nama_lengkap' => $h['namaLengkap'] ?? null,
                        'no_hp' => $h['noHp'] ?? null,
                    ]);

                    $totalPenginapan += $h['estimasiBiaya'] ?? 0;
                }

                $totalPeserta = $totalTransport + $totalPenginapan;

                $peserta->update([
                    'total_transport' => $totalTransport,
                    'total_accommodation' => $totalPenginapan,
                    'total_estimasion' => $totalPeserta,
                ]);

                $totalGrand += $totalPeserta;
            }

            $sppd->update([
                'grand_total' => $totalGrand
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'SPPD updated successfully',
                'data' => $sppd->load([
                    'peserta.transportasi',
                    'peserta.penginapan'
                ])
            ]);

        } catch (\Exception $e) {

            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE SPPD
     */

    #[OA\Delete(
        path: "/api/sppd/{id}",
        tags: ["SPPD"],
        summary: "Delete SPPD",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "SPPD ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [

            new OA\Response(
                response: 200,
                description: "Deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "status",
                            type: "boolean",
                            example: true
                        ),

                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Deleted successfully"
                        ),
                    ]
                )
            ),

            new OA\Response(
                response: 403,
                description: "Forbidden"
            ),

            new OA\Response(
                response: 404,
                description: "Data not found"
            ),
        ]
    )]

    public function destroy($id)
    {
        $user = auth()->user();

        $sppd = TrSppd::with([
            'requester'
        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | DIVISION ACCESS
        |--------------------------------------------------------------------------
        */

        if (
            !$user->hasPermission('sppd.delete.all')
            &&
            $sppd->requester?->division_id != $user->division_id
        ) {

            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki akses division'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | ONLY DRAFT
        |--------------------------------------------------------------------------
        */

        if (
            !$user->hasPermission('sppd.delete.all')
            &&
            $sppd->status !== 'draft'
        ) {

            return response()->json([
                'status' => false,
                'message' => 'SPPD hanya bisa dihapus saat draft'
            ], 403);
        }

        $sppd->delete();

        return response()->json([
            'status' => true,
            'message' => 'Deleted successfully'
        ]);
    }

    /**
     * SUBMIT SPPD
     */

    #[OA\Post(
        path: "/api/sppd/{id}/submit",
        tags: ["SPPD"],
        summary: "Submit SPPD",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "SPPD ID",
                schema: new OA\Schema(
                    type: "integer"
                )
            ),
        ],

        responses: [

            new OA\Response(
                response: 200,
                description: "SPPD submitted successfully",

                content: new OA\JsonContent(

                    properties: [

                        new OA\Property(
                            property: "status",
                            type: "boolean",
                            example: true
                        ),

                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "SPPD submitted successfully"
                        ),

                        new OA\Property(
                            property: "data",
                            type: "object"
                        ),
                    ]
                )
            ),

            new OA\Response(
                response: 400,
                description: "Invalid submit"
            ),

            new OA\Response(
                response: 403,
                description: "Forbidden"
            ),

            new OA\Response(
                response: 404,
                description: "SPPD not found"
            ),
        ]
    )]
    public function submit($id)
    {
        DB::beginTransaction();

        try {

            $user = auth()->user();

            $sppd = TrSppd::with([
                'requester',
                'approvals'
            ])->findOrFail($id);

            /*
            |----------------------------------------
            | VALIDASI STATUS
            |----------------------------------------
            */

            if ($sppd->approval_status !== 'draft') {

                return response()->json([
                    'status' => false,
                    'message' => 'SPPD hanya bisa di-submit dari status draft'
                ], 400);
            }

            /*
            |----------------------------------------
            | ACCESS CHECK
            |----------------------------------------
            */

            if ($sppd->requester_id !== $user->id) {

                return response()->json([
                    'status' => false,
                    'message' => 'Hanya requester yang bisa submit'
                ], 403);
            }

            /*
            |----------------------------------------
            | UPDATE SPPD
            |----------------------------------------
            */

            $sppd->update([
                'approval_status' => 'submitted',
                'submitted_at' => now(),
                'current_approval_level' => 1,
            ]);

            /*
            |----------------------------------------
            | CREATE APPROVAL LEVEL 1
            |----------------------------------------
            */

            $created = $this->createApprovalStep(
                sppdId: $sppd->id,
                flowId: $sppd->approval_flow_id,
                level: 1
            );

            if (!$created) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Approval flow level 1 tidak ditemukan'
                ], 400);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'SPPD submitted successfully',
                'data' => $sppd->load('approvals')
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}