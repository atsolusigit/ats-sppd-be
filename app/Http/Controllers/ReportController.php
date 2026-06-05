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
        $request->validate([
            'tujuan_perjalanan' => 'required|string',
            'ringkasan_hasil_kegiatan' => 'required|string',
            'lampiran' => 'nullable|array',
            'lampiran.*' => 'string',
        ]);

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

                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );
            $sppd->update([
                'approval_status' => 'completed'
            ]);

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
}