<?php

namespace App\Http\Controllers;

use App\Models\TrReport;
use App\Models\TrSppd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use App\Models\TrSppdTransportasi;
use App\Models\TrSppdPenginapan;
use App\Models\TrReportApproval;
use App\Services\ApprovalService;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;


class ReportApprovalController extends Controller
{
    #[OA\Get(
        path: "/api/report-approvals",
        tags: ["Report Approval"],
        summary: "List approval report",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "search",
                in: "query",
                required: false,
                description: "Cari tujuan perjalanan / ringkasan hasil kegiatan",
                schema: new OA\Schema(type: "string")
            ),

            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Status report",
                schema: new OA\Schema(
                    type: "string",
                    enum: [
                        "draft",
                        "submitted",
                        "approved",
                        "rejected",
                        "revision"
                    ]
                )
            ),

            new OA\Parameter(
                name: "approval_key",
                in: "query",
                required: false,
                description: "Filter approval key",
                schema: new OA\Schema(type: "string")
            ),

            new OA\Parameter(
                name: "approver_jabatan_id",
                in: "query",
                required: false,
                description: "Filter approver jabatan",
                schema: new OA\Schema(type: "integer")
            ),

            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10)
            ),
        ],

        responses: [

            new OA\Response(
                response: 200,
                description: "Success"
            ),

            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
        ]
    )]
    public function list(Request $request)
    {
        $user = auth()->user();

        $hasSuperAccess = $user->hasPermission('super.access');

        $query = TrReport::with([
            'sppd',
            'approvals.approver',
            'approvals.approverJabatan'
        ]);

        /*
        |--------------------------------------------------------------------------
        | ACCESS FILTER
        |--------------------------------------------------------------------------
        */
        if (!$hasSuperAccess) {

            $query->whereHas('approvals', function ($q) use ($user) {

                $q->where(
                    'approver_jabatan_id',
                    $user->jabatan_id
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'tujuan_perjalanan',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'ringkasan_hasil_kegiatan',
                    'like',
                    "%{$search}%"
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS REPORT
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER APPROVAL KEY
        |--------------------------------------------------------------------------
        */
        if ($request->filled('approval_key')) {

            $query->whereHas('approvals', function ($q) use ($request) {

                $q->where(
                    'approval_key',
                    $request->approval_key
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER APPROVER JABATAN
        |--------------------------------------------------------------------------
        */
        if ($request->filled('approver_jabatan_id')) {

            $query->whereHas('approvals', function ($q) use ($request) {

                $q->where(
                    'approver_jabatan_id',
                    $request->approver_jabatan_id
                );
            });
        }

        $query->latest('id');

        $paginated = $query->paginate(
            $request->get('per_page', 10)
        );

        $collection = $paginated->getCollection()->map(
            function ($item) use (
                $user,
                $hasSuperAccess
            ) {

                $approvals = $item->approvals
                    ->filter(function ($approval) use (
                        $user,
                        $hasSuperAccess
                    ) {

                        if ($hasSuperAccess) {
                            return true;
                        }

                        return
                            $approval->approver_jabatan_id ==
                            $user->jabatan_id;
                    })
                    ->values();

                return [

                    'id' => $item->id,

                    'sppd_id' => $item->sppd_id,

                    'approval_flow_id' => $item->approval_flow_id,

                    'status' => $item->status,

                    'tujuan_perjalanan' => $item->tujuan_perjalanan,

                    'ringkasan_hasil_kegiatan' =>
                        $item->ringkasan_hasil_kegiatan,

                    'submitted_at' => $item->submitted_at,

                    'approved_at' => $item->approved_at,

                    'approvals' => $approvals->map(
                        function ($approval) {

                            return [

                                'id' => $approval->id,

                                'approval_level' =>
                                    $approval->approval_level,

                                'approval_key' =>
                                    $approval->approval_key,

                                'approver_id' =>
                                    $approval->approver_id,

                                'approver_name' =>
                                    $approval->approver
                                        ? encrypt_decrypt_db(
                                            'dec',
                                            $approval->approver->name,
                                            $approval->approver->id
                                        )
                                        : null,

                                'approver_jabatan_id' =>
                                    $approval->approver_jabatan_id,

                                'approver_jabatan_name' =>
                                    $approval->approverJabatan?->name,

                                'status' =>
                                    $approval->status,

                                'notes' =>
                                    $approval->notes,

                                'approved_at' =>
                                    $approval->approved_at,
                            ];
                        }
                    )
                ];
            }
        );

        return response()->json([

            'status' => true,

            'pagination' => [

                'current_page' =>
                    $paginated->currentPage(),

                'last_page' =>
                    $paginated->lastPage(),

                'per_page' =>
                    $paginated->perPage(),

                'total' =>
                    $paginated->total(),
            ],

            'data' => $collection,
        ]);
    }

    #[OA\Post(
        path: "/api/report/{reportId}/approval",
        tags: ["Report Approval"],
        summary: "Approve / Reject / Revision Report",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "reportId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
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
                        ]
                    ),

                    new OA\Property(
                        property: "notes",
                        type: "string",
                        nullable: true
                    ),
                ]
            )
        ),

        responses: [

            new OA\Response(
                response: 200,
                description: "Approval berhasil diproses"
            ),

            new OA\Response(
                response: 400,
                description: "Validation error"
            ),

            new OA\Response(
                response: 403,
                description: "Forbidden"
            ),

            new OA\Response(
                response: 404,
                description: "Report tidak ditemukan"
            ),

            new OA\Response(
                response: 500,
                description: "Internal server error"
            ),
        ]
    )]

    public function action(Request $request, $reportId)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $request->validate([
            'status' => 'required|in:approved,rejected,revision',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {

            $report = TrReport::findOrFail($reportId);

            if (!in_array($report->status, [
                'submitted',
                'revision'
            ])) {

                return response()->json([
                    'status' => false,
                    'message' => 'Report tidak dalam proses approval'
                ], 400);
            }

            $currentLevel = $report->current_approval_level ?? 0;

            $approval = TrReportApproval::where(
                'report_id',
                $reportId
            )
            ->where(
                'approval_level',
                $currentLevel + 1
            )
            ->first();

            if (!$approval) {

                return response()->json([
                    'status' => false,
                    'message' => 'Approval level tidak ditemukan'
                ], 404);
            }

            $hasSuperAccess =
                $user->hasPermission('super.access');

            $hasJabatanAccess =
                $approval->approver_jabatan_id ==
                $user->jabatan_id;

            if (
                !$hasSuperAccess &&
                !$hasJabatanAccess
            ) {

                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses approval'
                ], 403);
            }

            if ($approval->status !== 'waiting') {

                return response()->json([
                    'status' => false,
                    'message' => 'Approval sudah diproses sebelumnya'
                ], 400);
            }

            $approval->update([
                'status' => $request->status,
                'notes' => $request->notes,
                'approved_at' => now(),
            ]);

            if ($request->status === 'approved') {

                $report->current_approval_level += 1;

                $nextWaiting = TrReportApproval::where(
                    'report_id',
                    $reportId
                )
                ->where('status', 'waiting')
                ->exists();

                if (!$nextWaiting) {

                    $report->status = 'approved';
                    $report->approved_at = now();

                    $report->sppd?->update([
                        'approval_status' => 'completed'
                    ]);

                } else {

                    $report->status = 'submitted';
                }

            } elseif ($request->status === 'rejected') {

                $report->status = 'rejected';

            } elseif ($request->status === 'revision') {

                $report->status = 'revision';
            }

            $report->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Approval report berhasil diproses',
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'REPORT Approval Error : ' .
                $e->getMessage()
            );

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}