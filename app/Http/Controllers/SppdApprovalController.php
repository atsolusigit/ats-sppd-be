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

class SppdApprovalController extends Controller
{
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
                    example: 1
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
                    example: 1
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
}