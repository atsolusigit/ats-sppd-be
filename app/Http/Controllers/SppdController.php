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
                name: "department_id",
                in: "query",
                required: false,
                description: "Filter department",
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
            'approval_flow',
            'approvals',
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
                'department_id',
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
                'approval_flow' => $item->approval_flow ? [
                    'id' => $item->approval_flow->id,
                    'name' => $item->approval_flow->name,
                ] : null,
                // 'approval_flow_id' => $item->approval_flow_id,
                'cost_center' => $item->cost_center,
                'kegiatan' => $item->kegiatan,
                'ringkasan_agenda' => $item->ringkasan_agenda,
                'approval_status' => $item->approval_status,
                'current_approval_level' => $item->current_approval_level,
                'grand_total' => $item->grand_total,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,

                'requester' => $item->requester ? [
                    'id' => $item->requester->id,
                    'name' => encrypt_decrypt_db('dec', $item->requester->name, $item->requester->id),
                ] : null,
                // 'department_id' => $item->department_id,
                'department' => $item->department ? [
                    'id' => $item->department->id,
                    'name' => $item->department->name,
                ] : null,

                'approvals' => $item->approvals->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'approval_level' => $a->approval_level,
                        'approver_id' => $a->approver_id,
                        'approver_name' => $a->approver ? encrypt_decrypt_db('dec', $a->approver->name, $a->approver->id) : null,
                        'approver_jabatan_id' => $a->approver_jabatan_id,
                        'approver_jabatan_name' => $a->approverJabatan ? $a->approverJabatan->name : null,
                        'status' => $a->status,
                        'notes' => $a->notes,
                        'approved_at' => $a->approved_at,
                    ];
                }),

                'peserta' => $item->peserta->map(function ($p) {

                    return [
                        'id' => $p->id ?? null,
                        'nama' => $p->nama ?? null,
                        'nip' => $p->nip ?? null,
                        'jabatan' => $p->jabatan ?? null,
                        'kota_asal' => $p->kota_asal ?? null,
                        'kota_tujuan' => $p->kota_tujuan ?? null,
                        'tempat_sppd' => $p->tempat_sppd ?? null,
                        'dari_tanggal' => $p->dari_tanggal ?? null,
                        'sampai_tanggal' => $p->sampai_tanggal ?? null,

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
            'approval_flow',
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
            'data' => [
                'id' => $data->id,
                'sppd_number' => $data->sppd_number,
                'jenis_dokumen' => $data->jenis_dokumen,
                'approval_status' => $data->approval_status,
                'cost_center' => $data->cost_center,
                'kegiatan' => $data->kegiatan,
                'ringkasan_agenda' => $data->ringkasan_agenda,
                'grand_total' => $data->grand_total,
                'submitted_at' => $data->submitted_at,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
                'current_approval_level' => $data->current_approval_level,
                'approval_flow' => $data->approval_flow ? [
                    'id' => $data->approval_flow->id,
                    'name' => $data->approval_flow->name,
                ] : null,

                'requester' => $data->requester ? [
                    'id' => $data->requester->id,
                    'name' => encrypt_decrypt_db(
                        'dec',
                        $data->requester->name,
                        $data->requester->id
                    ),
                ] : null,

                'peserta' => $data->peserta->map(function ($p) {

                    return [

                        'id' => $p->id,
                        'nama' => $p->nama,
                        'nip' => $p->nip,
                        'jabatan' => $p->jabatan,
                        'kota_asal' => $p->kota_asal,
                        'kota_tujuan' => $p->kota_tujuan,
                        'dari_tanggal' => $p->dari_tanggal,
                        'sampai_tanggal' => $p->sampai_tanggal,

                        'transportasi' => $p->transportasi,
                        'penginapan' => $p->penginapan,
                    ];
                }),
            ]
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
                    "departmentId",
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
                        property: "jenis_dokumen",
                        type: "bigint",
                        example: "1"
                    ),

                    new OA\Property(
                        property: "cost_center",
                        type: "string",
                        example: "CC001"
                    ),

                    new OA\Property(
                        property: "approval_flow_id",
                        type: "bigint",
                        example: "1"
                    ),

                    new OA\Property(
                        property: "department_id",
                        type: "bigint",
                        example: "1"
                    ),

                    new OA\Property(
                        property: "kegiatan",
                        type: "string",
                        example: "Meeting Client"
                    ),

                    new OA\Property(
                        property: "ringkasan_agenda",
                        type: "string",
                        example: "Pembahasan kerjasama project"
                    ),

                    new OA\Property(
                        property: "lampiran",
                        type: "array",
                        items: new OA\Items(
                            type: "string"
                        ),
                        example: ["file1.pdf", "file2.docx"]
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
                                    property: "kota_asal",
                                    type: "string",
                                    example: "Jakarta"
                                ),

                                new OA\Property(
                                    property: "kota_tujuan",
                                    type: "string",
                                    example: "Bandung"
                                ),

                                new OA\Property(
                                    property: "tempat_sppd",
                                    type: "string",
                                    example: "Gedung Balai Sidang Jakarta Convention Center"
                                ),

                                new OA\Property(
                                    property: "dari_tanggal",
                                    type: "string",
                                    format: "date"
                                ),

                                new OA\Property(
                                    property: "sampai_tanggal",
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
                                                property: "jenis_transportasi",
                                                type: "string",
                                                example: "Pesawat"
                                            ),

                                            new OA\Property(
                                                property: "nama_travel",
                                                type: "string",
                                                example: "Garuda Indonesia"
                                            ),

                                            new OA\Property(
                                                property: "asal_keberangkatan",
                                                type: "string",
                                                example: "Jakarta"
                                            ),

                                            new OA\Property(
                                                property: "tujuan_keberangkatan",
                                                type: "string",
                                                example: "Bandung"
                                            ),

                                            new OA\Property(
                                                property: "waktu",
                                                type: "string",
                                                format: "date-time"
                                            ),

                                            new OA\Property(
                                                property: "estimasi_biaya",
                                                type: "number",
                                                example: 1500000
                                            ),

                                            new OA\Property(
                                                property: "keterangan",
                                                type: "string",
                                                example: "PP"
                                            ),

                                            new OA\Property(
                                                property: "nama_lengkap",
                                                type: "string",
                                                example: "Bangkit"
                                            ),

                                            new OA\Property(
                                                property: "no_hp",
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
                                                property: "jenis_penginapan",
                                                type: "string",
                                                example: "Hotel"
                                            ),

                                            new OA\Property(
                                                property: "nama_tempat",
                                                type: "string",
                                                example: "Hotel Santika"
                                            ),

                                            new OA\Property(
                                                property: "lokasi",
                                                type: "string",
                                                example: "Bandung"
                                            ),

                                            new OA\Property(
                                                property: "check_in",
                                                type: "string",
                                                format: "date"
                                            ),

                                            new OA\Property(
                                                property: "check_out",
                                                type: "string",
                                                format: "date"
                                            ),

                                            new OA\Property(
                                                property: "estimasi_biaya",
                                                type: "number",
                                                example: 2500000
                                            ),

                                            new OA\Property(
                                                property: "keterangan",
                                                type: "string",
                                                example: "2 malam"
                                            ),

                                            new OA\Property(
                                                property: "nama_lengkap",
                                                type: "string",
                                                example: "Bangkit"
                                            ),

                                            new OA\Property(
                                                property: "no_hp",
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
                'jenis_dokumen' => $request->jenis_dokumen,
                'cost_center' => $request->cost_center,
                'approval_flow_id' => $request->approval_flow_id,
                'kegiatan' => $request->kegiatan,
                'ringkasan_agenda' => $request->ringkasan_agenda,
                'lampiran' => $request->lampiran ?? [],
                'requester_id' => auth()->id(),
                'requester_department_id' => auth()->user()->department_id,
                'requester_jabatan_id' => auth()->user()->jabatan_id,
                'department_id' => $request->department_id ?? auth()->user()->department_id,
                'approval_status' => 'draft'
            ]);
    
            $totalGrand = 0;

            foreach ($request->peserta as $p) {

                $peserta = $sppd->peserta()->create([
                    'nama' => $p['nama'] ?? null,
                    'nip' => $p['nip']?? null,
                    'jabatan' => $p['jabatan'] ?? null,
                    'kota_asal' => $p['kota_asal'] ?? null,
                    'kota_tujuan' => $p['kota_tujuan']?? null,
                    'tempat_sppd' => $p['tempat_sppd'] ?? null,
                    'dari_tanggal' => $p['dari_tanggal'] ?? null,
                    'sampai_tanggal' => $p['sampai_tanggal'] ?? null,
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
                        'jenis_transportasi' => $t['jenis_transportasi'] ?? null,
                        'nama_travel' => $t['nama_travel'] ?? null,
                        'asal_keberangkatan' => $t['asal_keberangkatan'] ?? null,
                        'tujuan_keberangkatan' => $t['tujuan_keberangkatan'] ?? null,
                        'waktu' => $t['waktu'] ?? null,
                        'estimasi_biaya' => $t['estimasi_biaya'] ?? 0,
                        'keterangan' => $t['keterangan'] ?? null,
                        'nama_lengkap' => $t['nama_lengkap'] ?? null,
                        'no_hp' => $t['no_hp'] ?? null,
                    ]);

                    $totalTransport += $t['estimasi_biaya'] ?? 0;
                }

                /*
                |--------------------------------------------------------------------------
                | PENGINAPAN
                |--------------------------------------------------------------------------
                */

                foreach ($p['penginapan'] ?? [] as $h) {

                    $peserta->penginapan()->create([
                        'jenis_penginapan' => $h['jenis_penginapan'] ?? null,
                        'nama_tempat' => $h['nama_tempat'] ?? null,
                        'lokasi' => $h['lokasi'] ?? null,
                        'check_in' => $h['check_in'] ?? null,
                        'check_out' => $h['check_out'] ?? null,
                        'estimasi_biaya' => $h['estimasi_biaya'] ?? 0,
                        'keterangan' => $h['keterangan'] ?? null,
                        'nama_lengkap' => $h['nama_lengkap'] ?? null,
                        'no_hp' => $h['no_hp'] ?? null,
                    ]);

                    $totalPenginapan += $h['estimasi_biaya'] ?? 0;
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
                required: ["jenis_dokumen", "jenis_perjalanan", "peserta"],
                properties: [

                    // HEADER
                    new OA\Property(
                        property: "jenis_dokumen",
                        type: "string",
                        example: "SPPD Direksi"
                    ),

                    new OA\Property(
                        property: "cost_center",
                        type: "string",
                        example: "CC001"
                    ),

                    new OA\Property(
                        property: "approval_flow_id",
                        type: "bigint",
                        example: "1"
                    ),

                    new OA\Property(
                        property: "kegiatan",
                        type: "string",
                        example: "Meeting Client"
                    ),

                    new OA\Property(
                        property: "ringkasan_agenda",
                        type: "string",
                        example: "Meeting kerja sama"
                    ),

                    new OA\Property(
                        property: "lampiran",
                        type: "array",
                        items: new OA\Items(
                            type: "string"
                        ),
                        example: ["file1.pdf", "file2.docx"]
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
                                    property: "kota_asal",
                                    type: "string",
                                    example: "Jakarta"
                                ),

                                new OA\Property(
                                    property: "kota_tujuan",
                                    type: "string",
                                    example: "Bandung"
                                ),

                                new OA\Property(
                                    property: "dari_tanggal",
                                    type: "string",
                                    format: "date"
                                ),

                                new OA\Property(
                                    property: "sampai_tanggal",
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
                                                property: "jenis_transportasi",
                                                type: "string",
                                                example: "Pesawat"
                                            ),

                                            new OA\Property(
                                                property: "nama_travel",
                                                type: "string",
                                                example: "Garuda"
                                            ),

                                            new OA\Property(
                                                property: "asal_keberangkatan",
                                                type: "string",
                                                example: "Jakarta"
                                            ),

                                            new OA\Property(
                                                property: "tujuan_keberangkatan",
                                                type: "string",
                                                example: "Bandung"
                                            ),

                                            new OA\Property(
                                                property: "waktu",
                                                type: "string",
                                                example: "2026-05-21 08:00:00"
                                            ),

                                            new OA\Property(
                                                property: "estimasi_biaya",
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
                                                property: "nama_lengkap",
                                                type: "string",
                                                example: "Bangkit"
                                            ),

                                            new OA\Property(
                                                property: "no_hp",
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
                                                property: "jenis_penginapan",
                                                type: "string",
                                                example: "Hotel"
                                            ),

                                            new OA\Property(
                                                property: "nama_tempat",
                                                type: "string",
                                                example: "Hilton"
                                            ),

                                            new OA\Property(
                                                property: "lokasi",
                                                type: "string",
                                                example: "Bandung"
                                            ),

                                            new OA\Property(
                                                property: "check_in",
                                                type: "string",
                                                format: "date"
                                            ),

                                            new OA\Property(
                                                property: "check_out",
                                                type: "string",
                                                format: "date"
                                            ),

                                            new OA\Property(
                                                property: "estimasi_biaya",
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
                                                property: "nama_lengkap",
                                                type: "string",
                                                example: "Bangkit"
                                            ),

                                            new OA\Property(
                                                property: "no_hp",
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
                $sppd->status !== 'draft' || $sppd->status === 'rejected'
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
                'jenis_dokumen' => $request->jenis_dokumen,
                'cost_center' => $request->cost_center,
                'approval_flow_id' => $request->approval_flow_id,
                'lampiran' => $request->lampiran ?? [],
                'kegiatan' => $request->kegiatan,
                'ringkasan_agenda' => $request->ringkasan_agenda,
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
                    'kota_asal' => $p['kota_asal'],
                    'kota_tujuan' => $p['kota_tujuan'],
                    'dari_tanggal' => $p['dari_tanggal'],
                    'sampai_tanggal' => $p['sampai_tanggal'],
                ]);

                $totalTransport = 0;
                $totalPenginapan = 0;

                foreach ($p['transportasi'] ?? [] as $t) {

                    $peserta->transportasi()->create([
                        'jenis_transportasi' => $t['jenis_transportasi'] ?? null,
                        'nama_travel' => $t['nama_travel'] ?? null,
                        'asal_keberangkatan' => $t['asal_keberangkatan'] ?? null,
                        'tujuan_keberangkatan' => $t['tujuan_keberangkatan'] ?? null,
                        'waktu' => $t['waktu'] ?? null,
                        'estimasi_biaya' => $t['estimasi_biaya'] ?? 0,
                        'keterangan' => $t['keterangan'] ?? null,
                        'nama_lengkap' => $t['nama_lengkap'] ?? null,
                        'no_hp' => $t['no_hp'] ?? null,
                    ]);

                    $totalTransport += $t['estimasi_biaya'] ?? 0;
                }

                foreach ($p['penginapan'] ?? [] as $h) {

                    $peserta->penginapan()->create([
                        'jenis_penginapan' => $h['jenis_penginapan'] ?? null,
                        'nama_tempat' => $h['nama_tempat'] ?? null,
                        'lokasi' => $h['lokasi'] ?? null,
                        'check_in' => $h['check_in'] ?? null,
                        'check_out' => $h['check_out'] ?? null,
                        'estimasi_biaya' => $h['estimasi_biaya'] ?? 0,
                        'keterangan' => $h['keterangan'] ?? null,
                        'nama_lengkap' => $h['nama_lengkap'] ?? null,
                        'no_hp' => $h['no_hp'] ?? null,
                    ]);

                    $totalPenginapan += $h['estimasi_biaya'] ?? 0;
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

    #[OA\Patch(
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

            if ($sppd->requester_id !== $user->id && !$user->hasPermission('sppd.submit.all')) {

                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses submit'
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
                'current_approval_level' => 0,
            ]);

            /*
            |----------------------------------------
            | CREATE APPROVAL LEVEL 1
            |----------------------------------------
            */

            $created = ApprovalHelper::createApprovalSteps(
                sppdId: $sppd->id,
                flowId: $sppd->approval_flow_id
            );

            if (!$created) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Approval flow tidak ditemukan'
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