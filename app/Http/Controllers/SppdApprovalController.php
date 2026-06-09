<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrSppd;
use App\Models\TrSppdApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use OpenApi\Attributes as OA;
use App\Traits\HasDynamicFilter;

class SppdApprovalController extends Controller
{
    use HasDynamicFilter;
    /**
     * APPROVE / REJECT / REVISION
     */

    #[OA\Post(
        path: "/api/sppd/{id}/approval",
        tags: ["SPPD Approval"],
        summary: "Approve / Reject / Revision SPPD",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "SPPD ID",
                schema: new OA\Schema(
                    type: "integer",
                    description: "SPPD ID"
                )
            ),
        ],

        requestBody: new OA\RequestBody(
            required: true,

            content: new OA\JsonContent(

                required: ["status"],

                properties: [

                    new OA\Property(
                        property: "status",
                        type: "string",
                        enum: [
                            "approved",
                            "rejected",
                            "revision"
                        ],
                        example: "approved"
                    ),

                    new OA\Property(
                        property: "notes",
                        type: "string",
                        nullable: true,
                        example: "Disetujui oleh manager"
                    ),
                ]
            )
        ),

        responses: [

            new OA\Response(
                response: 200,
                description: "Approval berhasil diproses",

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
                            example: "Approval berhasil diproses"
                        ),

                        new OA\Property(
                            property: "data",
                            properties: [

                                new OA\Property(
                                    property: "sppd_id",
                                    type: "integer",
                                    example: 1
                                ),

                                new OA\Property(
                                    property: "approval_status",
                                    type: "string",
                                    example: "approved"
                                ),

                                new OA\Property(
                                    property: "current_level",
                                    type: "integer",
                                    example: 2
                                ),

                                new OA\Property(
                                    property: "approval",
                                    properties: [

                                        new OA\Property(
                                            property: "approval_id",
                                            type: "integer",
                                            example: 10
                                        ),

                                        new OA\Property(
                                            property: "approval_level",
                                            type: "integer",
                                            example: 1
                                        ),

                                        new OA\Property(
                                            property: "status",
                                            type: "string",
                                            example: "approved"
                                        ),

                                        new OA\Property(
                                            property: "notes",
                                            type: "string",
                                            example: "Approved by manager"
                                        ),

                                        new OA\Property(
                                            property: "approved_at",
                                            type: "string",
                                            format: "date-time",
                                            example: "2026-05-23 10:00:00"
                                        ),

                                        new OA\Property(
                                            property: "approved_by",
                                            type: "integer",
                                            example: 1
                                        ),
                                    ],
                                    type: "object"
                                ),
                            ],
                            type: "object"
                        ),
                    ]
                )
            ),

            new OA\Response(
                response: 403,
                description: "Tidak memiliki akses approval"
            ),

            new OA\Response(
                response: 404,
                description: "Approval level tidak ditemukan"
            ),

            new OA\Response(
                response: 500,
                description: "Internal server error"
            ),
        ]
    )]
    public function action(Request $request, $sppdId)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $request->validate([
            'status' => 'required|in:approved,rejected,revision',
            'notes'  => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {

            $sppd = TrSppd::with([
                'approval_flow'
            ])->findOrFail($sppdId);

            /*
            |--------------------------------------------------------------------------
            | VALIDASI STATUS SPPD
            |--------------------------------------------------------------------------
            */

            if (!in_array($sppd->approval_status, [
                'submitted',
                'revision'
            ])) {

                return response()->json([
                    'status' => false,
                    'message' => 'SPPD tidak dalam proses approval'
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | GET CURRENT APPROVAL
            |--------------------------------------------------------------------------
            */

            $currentLevel = $sppd->current_approval_level ?? 0;

            $approval = TrSppdApproval::where('sppd_id', $sppdId)
                ->where('approval_level', $currentLevel + 1)
                ->first();

            if (!$approval) {

                return response()->json([
                    'status' => false,
                    'message' => 'Approval level tidak ditemukan'
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDASI HAK APPROVAL
            |--------------------------------------------------------------------------
            */

            $hasSuperAccess = $user->hasPermission('super.access');

            $hasJabatanAccess =
                $approval->approver_jabatan_id ==
                $user->jabatan_id;

            if (
                !$hasSuperAccess
                &&
                !$hasJabatanAccess
            ) {

                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses approval'
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | CEK SUDAH DIPROSES
            |--------------------------------------------------------------------------
            */

            if ($approval->status !== 'waiting') {

                return response()->json([
                    'status' => false,  
                    'message' => 'Approval sudah diproses sebelumnya'
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE APPROVAL
            |--------------------------------------------------------------------------
            */

            $approval->update([
                'status' => $request->status,
                'notes' => $request->notes,
                'approver_id' => $user->id,
                'approved_at' => Carbon::now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | LOGIC STATUS
            |--------------------------------------------------------------------------
            */

            if ($request->status === 'approved') {

                $sppd->current_approval_level += 1;

                /*
                |--------------------------------------------------------------------------
                | CHECK NEXT APPROVAL
                |--------------------------------------------------------------------------
                */

                $nextWaiting = TrSppdApproval::where(
                    'sppd_id',
                    $sppdId
                )
                    ->where('status', 'waiting')
                    ->exists();

                if (!$nextWaiting) {

                    $sppd->approval_status = 'approved';

                } else {

                    $sppd->approval_status = 'submitted';
                }

            } elseif ($request->status === 'rejected') {

                $sppd->approval_status = 'rejected';

            } elseif ($request->status === 'revision') {

                $sppd->approval_status = 'revision';
            }elseif ($request->status === 'cancelled') {

                if (!in_array($sppd->approval_status, [
                    'approved',
                    'rejected'
                ])) {

                    return response()->json([
                        'status' => false,
                        'message' => 'SPPD hanya dapat dibatalkan jika status saat ini approved atau rejected'
                    ], 422);
                }

                $sppd->approval_status = 'cancelled';
            }

            $sppd->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Approval berhasil diproses',

                'data' => [
                    'sppd_id' => $sppd->id,
                    'approval_status' => $sppd->approval_status,
                    'current_level' => $sppd->current_approval_level,

                    'approval' => [
                        'approval_id' => $approval->id,
                        'approval_level' => $approval->approval_level,
                        'status' => $approval->status,
                        'notes' => $approval->notes,
                        'approved_at' => $approval->approved_at,
                        'approved_by' => $user->id,
                    ]
                ]
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('SPPD Approval Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET APPROVAL HISTORY
     */

    #[OA\Get(
        path: "/api/sppd/{id}/approval-history",
        tags: ["SPPD Approval"],
        summary: "Get Approval History SPPD",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "SPPD ID",
                schema: new OA\Schema(
                    type: "integer",
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
                            type: "array",

                            items: new OA\Items(

                                properties: [

                                    new OA\Property(
                                        property: "id",
                                        type: "integer",
                                        example: 1
                                    ),

                                    new OA\Property(
                                        property: "approval_level",
                                        type: "integer",
                                        example: 1
                                    ),

                                    new OA\Property(
                                        property: "status",
                                        type: "string",
                                        example: "approved"
                                    ),

                                    new OA\Property(
                                        property: "notes",
                                        type: "string",
                                        example: "Approved by manager"
                                    ),

                                    new OA\Property(
                                        property: "approved_at",
                                        type: "string",
                                        format: "date-time",
                                        example: "2026-05-23 10:00:00"
                                    ),

                                    new OA\Property(
                                        property: "approver",
                                        type: "object",

                                        properties: [

                                            new OA\Property(
                                                property: "id",
                                                type: "integer",
                                                example: 1
                                            ),

                                            new OA\Property(
                                                property: "name",
                                                type: "string",
                                                example: "Bangkit"
                                            ),
                                        ]
                                    ),

                                    new OA\Property(
                                        property: "approver_jabatan",
                                        type: "object",

                                        properties: [

                                            new OA\Property(
                                                property: "id",
                                                type: "integer",
                                                example: 2
                                            ),

                                            new OA\Property(
                                                property: "name",
                                                type: "string",
                                                example: "Manager IT"
                                            ),
                                        ]
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            ),

            new OA\Response(
                response: 404,
                description: "Data not found"
            ),
        ]
    )]
    public function history($sppdId)
    {
        $data = TrSppdApproval::with([
            'approver',
            'approverJabatan'
        ])
            ->where('sppd_id', $sppdId)
            ->orderBy('approval_level', 'asc')
            ->get()
            ->map(function ($item) {

                return [
                    'id' => $item->id,

                    'approval_level' => $item->approval_level,

                    'status' => $item->status,

                    'notes' => $item->notes,

                    'approved_at' => $item->approved_at,

                    'approver' => $item->approver ? [
                        'id' => $item->approver->id,
                        'name' => encrypt_decrypt_db(
                            'dec',
                            $item->approver->name,
                            $item->approver->id
                        ),
                    ] : null,

                    'approver_jabatan' => $item->approverJabatan ? [
                        'id' => $item->approverJabatan->id,
                        'name' => $item->approverJabatan->name,
                    ] : null,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    #[OA\Get(
        path: "/api/approval-list",
        tags: ["SPPD Approval"],
        summary: "Get list approval SPPD",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "approval_status",
                in: "query",
                required: false,
                description: "Filter status approval SPPD",
                schema: new OA\Schema(
                    type: "string",
                )
            ),

            new OA\Parameter(
                name: "approval_flow_id",
                in: "query",
                required: false,
                description: "Filter approval flow",
                schema: new OA\Schema(
                    type: "integer",
                )
            ),

            new OA\Parameter(
                name: "current_approval_level",
                in: "query",
                required: false,
                description: "Filter current approval level",
                schema: new OA\Schema(
                    type: "integer",
                )
            ),

            new OA\Parameter(
                name: "approval_level",
                in: "query",
                required: false,
                description: "Filter approval level (untuk melihat SPPD yang butuh approval di level tertentu)",
                schema: new OA\Schema(
                    type: "integer",
                )
            ),

            new OA\Parameter(
                name: "approver_jabatan_id",
                in: "query",
                required: false,
                description: "Filter jabatan approver (untuk melihat SPPD yang butuh approval oleh jabatan tertentu)",
                schema: new OA\Schema(
                    type: "integer",
                )
            ),

            new OA\Parameter(
                name: "approval_key",
                in: "query",
                required: false,
                description: "Filter approval key (director / direct_manager / finance / human_resource)",
                schema: new OA\Schema(
                    type: "string",
                )
            ),

            new OA\Parameter(
                name: "department_id",
                in: "query",
                required: false,
                description: "Filter department",
                schema: new OA\Schema(
                    type: "integer",
                )
            ),

            new OA\Parameter(
                name: "requester_id",
                in: "query",
                required: false,
                description: "Filter requester",
                schema: new OA\Schema(
                    type: "integer",
                )
            ),

            new OA\Parameter(
                name: "search",
                in: "query",
                required: false,
                description: "Search sppd_number, cost_center, kegiatan, ringkasan_agenda",
                schema: new OA\Schema(
                    type: "string",
                )
            ),

            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                description: "Jumlah data per halaman",
                schema: new OA\Schema(
                    type: "integer",
                    default: 10,
                )
            ),

            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                description: "Nomor halaman",
                schema: new OA\Schema(
                    type: "integer",
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
                            property: "pagination",
                            type: "object",

                            properties: [

                                new OA\Property(
                                    property: "current_page",
                                    type: "integer",
                                    example: 1
                                ),

                                new OA\Property(
                                    property: "last_page",
                                    type: "integer",
                                    example: 5
                                ),

                                new OA\Property(
                                    property: "per_page",
                                    type: "integer",
                                    example: 10
                                ),

                                new OA\Property(
                                    property: "total",
                                    type: "integer",
                                    example: 50
                                ),
                            ]
                        ),

                        new OA\Property(
                            property: "data",
                            type: "array",

                            items: new OA\Items(

                                properties: [

                                    new OA\Property(
                                        property: "id",
                                        type: "integer",
                                        example: 71
                                    ),

                                    new OA\Property(
                                        property: "sppd_number",
                                        type: "string",
                                        example: "SPPD-20260529091359"
                                    ),

                                    new OA\Property(
                                        property: "approval_status",
                                        type: "string",
                                        example: "submitted"
                                    ),

                                    new OA\Property(
                                        property: "current_approval_level",
                                        type: "integer",
                                        example: 1
                                    ),

                                    new OA\Property(
                                        property: "grand_total",
                                        type: "number",
                                        format: "float",
                                        example: 4000000
                                    ),

                                    new OA\Property(
                                        property: "requester",
                                        type: "object",

                                        properties: [

                                            new OA\Property(
                                                property: "id",
                                                type: "integer",
                                                example: 8
                                            ),

                                            new OA\Property(
                                                property: "name",
                                                type: "string",
                                                example: "superAdmin"
                                            ),
                                        ]
                                    ),

                                    new OA\Property(
                                        property: "approval_flow",
                                        type: "object",

                                        properties: [

                                            new OA\Property(
                                                property: "id",
                                                type: "integer",
                                                example: 1
                                            ),

                                            new OA\Property(
                                                property: "name",
                                                type: "string",
                                                example: "Domestik Dalam Kota"
                                            ),
                                        ]
                                    ),

                                    new OA\Property(
                                        property: "approvals",
                                        type: "array",

                                        items: new OA\Items(

                                            properties: [

                                                new OA\Property(
                                                    property: "id",
                                                    type: "integer",
                                                    example: 54
                                                ),

                                                new OA\Property(
                                                    property: "approval_level",
                                                    type: "integer",
                                                    example: 1
                                                ),

                                                new OA\Property(
                                                    property: "approver_id",
                                                    type: "integer",
                                                    nullable: true,
                                                    example: 8
                                                ),

                                                new OA\Property(
                                                    property: "approver_name",
                                                    type: "string",
                                                    nullable: true,
                                                    example: "superAdmin"
                                                ),

                                                new OA\Property(
                                                    property: "approver_jabatan_id",
                                                    type: "integer",
                                                    nullable: true,
                                                    example: 2
                                                ),

                                                new OA\Property(
                                                    property: "approver_jabatan_name",
                                                    type: "string",
                                                    nullable: true,
                                                    example: "IT Manager"
                                                ),

                                                new OA\Property(
                                                    property: "status",
                                                    type: "string",
                                                    example: "approved"
                                                ),

                                                new OA\Property(
                                                    property: "notes",
                                                    type: "string",
                                                    nullable: true,
                                                    example: "lv 1 ok"
                                                ),

                                                new OA\Property(
                                                    property: "approved_at",
                                                    type: "string",
                                                    format: "date-time",
                                                    nullable: true,
                                                    example: "2026-05-29T09:43:53.000000Z"
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

            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),

            new OA\Response(
                response: 403,
                description: "Forbidden"
            ),
        ]
    )]
    // public function list(Request $request)
    // {
    //     $user = auth()->user();

    //     $hasSuperAccess = $user->hasPermission('super.access');

    //     $query = TrSppd::with([
    //         'requester',
    //         'approval_flow',
    //         'approvals.approver',
    //         'approvals.approverJabatan'
    //     ]);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | HIDE DRAFT
    //     |--------------------------------------------------------------------------
    //     */
    //     $query->where('approval_status', '!=', 'draft');

    //     /*
    //     |--------------------------------------------------------------------------
    //     | ACCESS FILTER
    //     |--------------------------------------------------------------------------
    //     */
    //     if (!$hasSuperAccess) {

    //         $query->whereHas('approvals', function ($q) use ($user) {

    //             $q->where('approver_jabatan_id', $user->jabatan_id)
    //                 ->whereRaw(
    //                     'approval_level <= tr_sppd.current_approval_level + 1'
    //                 );
    //         });
    //     }

    //     $query = $this->applyFilters(
    //         query: $query,
    //         request: $request,

    //         allowedFilters: [
    //             'approval_status',
    //             'approval_flow_id',
    //             'current_approval_level',
    //             'department_id',
    //             'requester_id',
    //         ],

    //         searchableFields: [
    //             'sppd_number',
    //             'cost_center',
    //             'kegiatan',
    //             'ringkasan_agenda',
    //         ]
    //     );
    //     /*
    //     |--------------------------------------------------------------------------
    //     | SORTING
    //     |--------------------------------------------------------------------------
    //     */
    //     // $query->latest('id')
    //     $query->reorder();
    //     $query->orderByRaw("
    //         FIELD(
    //             approval_status,
    //             'draft',
    //             'submitted',
    //             'approved',
    //             'rejected',
    //             'revision',
    //             'completed',
    //             'cancelled'
    //         )
    //     ")->latest('id');


    //     /*
    //     |--------------------------------------------------------------------------
    //     | PAGINATION
    //     |--------------------------------------------------------------------------
    //     */
    //     $approvalLevelFilter = $request->get('approval_level');
    //     if (!empty($approvalLevelFilter)) {

    //     $query->where(
    //         'current_approval_level',
    //         '>=',
    //         ((int)$approvalLevelFilter - 1)
    //     );

    //     if (!empty($approvalLevelFilter)) {

    //         $query->whereHas('approvals', function ($q) use ($approvalLevelFilter) {
    //             $q->where('approval_level', $approvalLevelFilter);
    //         });

    //         $query->where(
    //             'current_approval_level',
    //             '>=',
    //             ((int)$approvalLevelFilter - 1)
    //         );
    //     }
    //     }
    //     $paginated = $query->paginate(
    //         $request->get('per_page', 10)
    //     );

    //     /*
    //     |--------------------------------------------------------------------------
    //     | FORMAT RESPONSE
    //     |--------------------------------------------------------------------------
    //     */
    //     $collection = $paginated->getCollection()->map(
    //         function ($item) use ($user, $hasSuperAccess, $approvalLevelFilter) {

    //             $approvals = $item->approvals
    //                 ->filter(function ($approval) use (
    //                     $user,
    //                     $hasSuperAccess,
    //                     $approvalLevelFilter,
    //                 ) {

    //                     // filter approval level
    //                     if (!empty($approvalLevelFilter)) {

    //                         if (
    //                             $approval->approval_level !=
    //                             (int) $approvalLevelFilter
    //                         ) {
    //                             return false;
    //                         }
    //                     }

    //                     // super access lihat semua
    //                     if ($hasSuperAccess) {
    //                         return true;
    //                     }

    //                     // user biasa hanya lihat approval sesuai jabatannya
    //                     return $approval->approver_jabatan_id == $user->jabatan_id;
    //                 })
    //                 ->sortBy('approval_level')
    //                 ->values();
    //             return [

    //                 'id' => $item->id,

    //                 'sppd_number' => $item->sppd_number,

    //                 'jenis_dokumen' => $item->jenis_dokumen,

    //                 'cost_center' => $item->cost_center,

    //                 'kegiatan' => $item->kegiatan,

    //                 'ringkasan_agenda' => $item->ringkasan_agenda,

    //                 'approval_status' => $item->approval_status,

    //                 'current_approval_level' => $item->current_approval_level,

    //                 'grand_total' => $item->grand_total,

    //                 'created_at' => $item->created_at,

    //                 'updated_at' => $item->updated_at,

    //                 'requester' => $item->requester ? [

    //                     'id' => $item->requester->id,

    //                     'name' => encrypt_decrypt_db(
    //                         'dec',
    //                         $item->requester->name,
    //                         $item->requester->id
    //                     ),

    //                 ] : null,

    //                 'approval_flow' => $item->approval_flow ? [

    //                     'id' => $item->approval_flow->id,

    //                     'name' => $item->approval_flow->name,

    //                 ] : null,

    //                 'approvals' => $approvals->map(function ($approval) {

    //                     return [

    //                         'id' => $approval->id,

    //                         'approval_level' => $approval->approval_level,

    //                         'approver_id' => $approval->approver_id,

    //                         'approver_name' => $approval->approver
    //                             ? encrypt_decrypt_db(
    //                                 'dec',
    //                                 $approval->approver->name,
    //                                 $approval->approver->id
    //                             )
    //                             : null,

    //                         'approver_jabatan_id' => $approval->approver_jabatan_id,

    //                         'approver_jabatan_name' => $approval->approverJabatan?->name,

    //                         'status' => $approval->status,

    //                         'notes' => $approval->notes,

    //                         'approved_at' => $approval->approved_at,
    //                     ];
    //                 }),
    //             ];
    //         }
    //     );

    //     return response()->json([

    //         'status' => true,

    //         'pagination' => [

    //             'current_page' => $paginated->currentPage(),

    //             'last_page' => $paginated->lastPage(),

    //             'per_page' => $paginated->perPage(),

    //             'total' => $paginated->total(),
    //         ],

    //         'data' => $collection,
    //     ]);
    // }

    public function list(Request $request)
    {
        $user = auth()->user();

        $hasSuperAccess = $user->hasPermission('super.access');

        $query = TrSppd::with([
            'requester',
            'approval_flow',
            'approvals.approver',
            'approvals.approverJabatan'
        ]);

        /*
        |--------------------------------------------------------------------------
        | HIDE DRAFT
        |--------------------------------------------------------------------------
        */
        $query->where('approval_status', '!=', 'draft');

        /*
        |--------------------------------------------------------------------------
        | ACCESS FILTER
        |--------------------------------------------------------------------------
        */
        if (!$hasSuperAccess) {

            $query->whereHas('approvals', function ($q) use ($user) {

                $q->where('approver_jabatan_id', $user->jabatan_id)
                    ->whereRaw(
                        'approval_level <= tr_sppd.current_approval_level + 1'
                    );
            });
        }

        $query = $this->applyFilters(
            query: $query,
            request: $request,

            allowedFilters: [
                'approval_status',
                'approval_flow_id',
                'current_approval_level',
                'department_id',
                'requester_id',
            ],

            searchableFields: [
                'sppd_number',
                'cost_center',
                'kegiatan',
                'ringkasan_agenda',
            ]
        );
        /*
        |--------------------------------------------------------------------------
        | SORTING
        |--------------------------------------------------------------------------
        */
        // $query->latest('id')
        $query->reorder();
        $query->orderByRaw("
            FIELD(
                approval_status,
                'draft',
                'submitted',
                'approved',
                'rejected',
                'revision',
                'completed',
                'cancelled'
            )
        ")->latest('id');


        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */
        $approvalLevelFilter = $request->get('approval_level');
        if (!empty($approvalLevelFilter)) {

        $query->where(
            'current_approval_level',
            '>=',
            ((int)$approvalLevelFilter - 1)
        );

        if (!empty($approvalLevelFilter)) {

            $query->whereHas('approvals', function ($q) use ($approvalLevelFilter) {
                $q->where('approval_level', $approvalLevelFilter);
            });

            $query->where(
                'current_approval_level',
                '>=',
                ((int)$approvalLevelFilter - 1)
            );
        }
        }

        $approvalKey = $request->get('approval_key');
        $approverJabatanId = $request->get('approver_jabatan_id');

        if (!empty($approvalKey)) {

            $query->whereHas('approvals', function ($q) use ($approvalKey) {

                $q->where('approval_key', $approvalKey);
            });
        }

        if (!empty($approverJabatanId)) {

            $query->whereHas('approvals', function ($q) use ($approverJabatanId) {

                $q->where(
                    'approver_jabatan_id',
                    (int) $approverJabatanId
                );
            });
        }
        $paginated = $query->paginate(
            $request->get('per_page', 10)
        );

        /*
        |--------------------------------------------------------------------------
        | FORMAT RESPONSE
        |--------------------------------------------------------------------------
        */
        $collection = $paginated->getCollection()->map(
            function ($item) use ($user, $hasSuperAccess, $approvalLevelFilter, $approvalKey, $approverJabatanId) {

                $approvals = $item->approvals
                    ->filter(function ($approval) use (
                        $user,
                        $hasSuperAccess,
                        $approvalLevelFilter,
                        $approvalKey,
                        $approverJabatanId,

                    ) {

                        // filter approval level
                        if (!empty($approvalLevelFilter)) {

                            if (
                                $approval->approval_level !=
                                (int) $approvalLevelFilter
                            ) {
                                return false;
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | FILTER APPROVAL KEY
                        |--------------------------------------------------------------------------
                        */
                        if (
                            !empty($approvalKey)
                            &&
                            $approval->approval_key !== $approvalKey
                        ) {
                            return false;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | FILTER JABATAN
                        |--------------------------------------------------------------------------
                        */
                        if (
                            !empty($approverJabatanId)
                            &&
                            $approval->approver_jabatan_id != (int)$approverJabatanId
                        ) {
                            return false;
                        }

                        // super access lihat semua
                        if ($hasSuperAccess) {
                            return true;
                        }

                        // user biasa hanya lihat approval sesuai jabatannya
                        return $approval->approver_jabatan_id == $user->jabatan_id;
                    })
                    ->sortBy('approval_level')
                    ->values();
                return [

                    'id' => $item->id,

                    'sppd_number' => $item->sppd_number,

                    'jenis_dokumen' => $item->jenis_dokumen,

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

                        'name' => encrypt_decrypt_db(
                            'dec',
                            $item->requester->name,
                            $item->requester->id
                        ),

                    ] : null,

                    'approval_flow' => $item->approval_flow ? [

                        'id' => $item->approval_flow->id,

                        'name' => $item->approval_flow->name,

                    ] : null,

                    'approvals' => $approvals->map(function ($approval) {

                        return [

                            'id' => $approval->id,

                            'approval_level' => $approval->approval_level,

                            'approval_key' => $approval->approval_key,

                            'approver_id' => $approval->approver_id,

                            'approver_name' => $approval->approver
                                ? encrypt_decrypt_db(
                                    'dec',
                                    $approval->approver->name,
                                    $approval->approver->id
                                )
                                : null,

                            'approver_jabatan_id' => $approval->approver_jabatan_id,

                            'approver_jabatan_name' => $approval->approverJabatan?->name,

                            'status' => $approval->status,

                            'notes' => $approval->notes,

                            'approved_at' => $approval->approved_at,
                        ];
                    }),
                ];
            }
        );

        return response()->json([

            'status' => true,

            'pagination' => [

                'current_page' => $paginated->currentPage(),

                'last_page' => $paginated->lastPage(),

                'per_page' => $paginated->perPage(),

                'total' => $paginated->total(),
            ],

            'data' => $collection,
        ]);
    }
}