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
use App\Helpers\ApprovalHelper;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Create / Update Report
     */

    #[OA\Post(
        path: "/api/reports",
        tags: ["SPPD Report"],
        summary: "Create Report Pertanggungjawaban",
        security: [["bearerAuth" => []]],

        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(

                required: [
                    "sppd_id",
                    "tujuan_perjalanan",
                    "ringkasan_hasil_kegiatan"
                ],

                properties: [

                    new OA\Property(
                        property: "sppd_id",
                        type: "integer",
                        example: 1
                    ),

                    new OA\Property(
                        property: "approval_flow_id",
                        type: "integer",
                        example: 1
                    ),

                    new OA\Property(
                        property: "tujuan_perjalanan",
                        type: "string",
                        example: "Jakarta"
                    ),

                    new OA\Property(
                        property: "ringkasan_hasil_kegiatan",
                        type: "string",
                        example: "Menghadiri rapat koordinasi nasional"
                    ),

                    new OA\Property(
                        property: "lampiran",
                        type: "array",
                        items: new OA\Items(type: "string"),
                        example: [
                            "https://files.company.com/report/foto1.jpg",
                            "https://files.company.com/report/notulen.pdf"
                        ]
                    ),
                ]
            )
        ),

        responses: [

            new OA\Response(
                response: 200,
                description: "Report berhasil dibuat"
            ),

            new OA\Response(
                response: 422,
                description: "Validation Error"
            )
        ]
    )]
    public function store(Request $request)
    {

        $request->validate([
            'approval_flow_id' => 'required|exists:mst_approval_flows,id',
            'tujuan_perjalanan' => 'required|string',
            'ringkasan_hasil_kegiatan' => 'required|string',
            'lampiran' => 'nullable|array',
            'lampiran.*' => 'string',
        ]);

        $sppdId = $request->sppd_id;

        $sppd = TrSppd::findOrFail($sppdId);

        $transportEmpty = TrSppdTransportasi::whereHas('peserta', function ($q) use ($sppd) {
            $q->where('sppd_id', $sppd->id);
        })
        ->whereNull('actual_biaya')
        ->exists();

        $accommodationEmpty = TrSppdPenginapan::whereHas('peserta', function ($q) use ($sppd) {
            $q->where('sppd_id', $sppd->id);
        })
        ->whereNull('actual_biaya')
        ->exists();

        if ($transportEmpty || $accommodationEmpty) {
            return response()->json([
                'status' => false,
                'message' => 'Realisasi belum lengkap. Pastikan semua biaya transportasi dan akomodasi sudah diisi.'
            ], 422);
        }

        DB::beginTransaction();

        try {

            $report = TrReport::updateOrCreate(
                [
                    'sppd_id' => $sppd->id
                ],
                [
                    'tujuan_perjalanan' => $request->tujuan_perjalanan,
                    'ringkasan_hasil_kegiatan' => $request->ringkasan_hasil_kegiatan,
                    'lampiran' => $request->lampiran ?? [],
                    'status' => 'draft',
                    'approval_flow_id' => $request->approval_flow_id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );
            // $sppd->update([
            //     'approval_status' => 'completed'
            // ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Report berhasil disimpan',
                'data' => $report
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error($e->getMessage());

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail Report berdasarkan SPPD
     */

    #[OA\Get(
        path: "/api/reports/{id}",
        tags: ["SPPD Report"],
        summary: "Detail Report",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(
                    type: "integer"
                )
            )
        ],

        responses: [

            new OA\Response(
                response: 200,
                description: "Success"
            ),

            new OA\Response(
                response: 404,
                description: "Report not found"
            )
        ]
    )]
    public function show($sppdId)
    {
        $report = TrReport::with([
            'sppd'
        ])
        ->where('sppd_id', $sppdId)
        ->first();

        if (!$report) {

            return response()->json([
                'status' => false,
                'message' => 'Report tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $report
        ]);
    }

    /**
     * List Report
     */

    #[OA\Get(
        path: "/api/reports",
        tags: ["SPPD Report"],
        summary: "List Report",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "sppd_id",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "integer"
                )
            ),

            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "integer"
                )
            ),

            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    example: 10
                )
            )
        ],

        responses: [

            new OA\Response(
                response: 200,
                description: "Success"
            )
        ]
    )]
    public function index(Request $request)
    {
        $query = TrReport::with([
            'sppd'
        ]);

        if ($request->filled('sppd_id')) {
            $query->where(
                'sppd_id',
                $request->sppd_id
            );
        }

        $data = $query
            ->latest()
            ->paginate(
                $request->get('per_page', 10)
            );

        return response()->json([
            'status' => true,
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
            'data' => $data->items()
        ]);
    }

    /**
     * Delete Report
     */

    #[OA\Delete(
        path: "/api/reports/{id}",
        tags: ["SPPD Report"],
        summary: "Delete Report",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(
                    type: "integer"
                )
            )
        ],

        responses: [

            new OA\Response(
                response: 200,
                description: "Report berhasil dihapus"
            ),

            new OA\Response(
                response: 404,
                description: "Report tidak ditemukan"
            )
        ]
)]
    public function destroy($id)
    {
        $report = TrReport::find($id);

        if (!$report) {

            return response()->json([
                'status' => false,
                'message' => 'Report tidak ditemukan'
            ], 404);
        }

        $report->delete();

        return response()->json([
            'status' => true,
            'message' => 'Report berhasil dihapus'
        ]);
    }

    #[OA\Post(
        path: "/api/reports/{sppdId}/submit",
        tags: ["SPPD Report"],
        summary: "Submit Report untuk Approval",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "sppdId",
                in: "path",
                required: true,
                description: "ID SPPD",
                schema: new OA\Schema(
                    type: "integer",
                )
            )
        ],

        responses: [

            new OA\Response(
                response: 200,
                description: "Report submitted successfully"
            ),

            new OA\Response(
                response: 400,
                description: "Report bukan draft"
            ),

            new OA\Response(
                response: 404,
                description: "Report tidak ditemukan"
            )
        ]
    )]
    public function submit($sppdId)
    {
        DB::beginTransaction();

        try {

            $report = TrReport::where('sppd_id', $sppdId)->firstOrFail();

            if ($report->status !== 'draft') {
                return response()->json([
                    'status' => false,
                    'message' => 'Report hanya bisa disubmit dari draft'
                ], 400);
            }

            $report->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'current_approval_level' => 0,
            ]);

            ApprovalHelper::createApprovalStepsReport(
                reportId: $report->id,
                flowId: $report->approval_flow_id
            );

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Report submitted successfully'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/reports/{reportId}/action",
        tags: ["SPPD Report"],
        summary: "Approve / Reject / Revision Report",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "reportId",
                in: "path",
                required: true,
                description: "ID Report",
                schema: new OA\Schema(
                    type: "integer",
                )
            )
        ],

        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(

                required: ["status"],

                properties: [

                    new OA\Property(
                        property: "status",
                        type: "string",
                        enum: ["approved", "rejected", "revision"],
                        example: "approved"
                    ),

                    new OA\Property(
                        property: "notes",
                        type: "string",
                        nullable: true,
                        example: "Report sudah sesuai"
                    ),
                ]
            )
        ),

        responses: [

            new OA\Response(
                response: 200,
                description: "Approval report berhasil diproses"
            ),

            new OA\Response(
                response: 403,
                description: "Tidak memiliki hak approval"
            ),

            new OA\Response(
                response: 404,
                description: "Approval level tidak ditemukan"
            ),

            new OA\Response(
                response: 500,
                description: "Terjadi kesalahan sistem"
            )
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

            /*
            |---------------------------------------------------------
            | VALIDASI STATUS REPORT
            |---------------------------------------------------------
            */

            if (!in_array($report->status, [
                'submitted',
                'revision'
            ])) {

                return response()->json([
                    'status' => false,
                    'message' => 'Report tidak dalam proses approval'
                ], 400);
            }

            /*
            |---------------------------------------------------------
            | GET CURRENT APPROVAL
            |---------------------------------------------------------
            */

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

            /*
            |---------------------------------------------------------
            | VALIDASI HAK APPROVAL
            |---------------------------------------------------------
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
            |---------------------------------------------------------
            | CEK SUDAH DIPROSES
            |---------------------------------------------------------
            */

            if ($approval->status !== 'waiting') {

                return response()->json([
                    'status' => false,
                    'message' => 'Approval sudah diproses sebelumnya'
                ], 400);
            }

            /*
            |---------------------------------------------------------
            | UPDATE APPROVAL
            |---------------------------------------------------------
            */

            $approval->update([
                'status' => $request->status,
                'notes' => $request->notes,
                'approved_at' => now(),
            ]);

            /*
            |---------------------------------------------------------
            | LOGIC STATUS REPORT
            |---------------------------------------------------------
            */

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

                    /*
                    |---------------------------------------------------------
                    | OPTIONAL:
                    | Setelah report approved,
                    | SPPD menjadi completed
                    |---------------------------------------------------------
                    */

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

                'data' => [
                    'report_id' => $report->id,
                    'status' => $report->status,
                    'current_level' => $report->current_approval_level,

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

            Log::error(
                'REPORT Approval Error: ' .
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