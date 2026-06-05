<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrSppdTransportasi;
use App\Models\TrSppdPenginapan;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use App\Models\TrSppd;

class SppdRealisasiController extends Controller
{
    
    #[OA\Put(
        path: "/api/sppd/realisasi/update",
        summary: "Update realisasi biaya SPPD",
        tags: ["SPPD Realisasi"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["sppd_id"],
                properties: [

                    new OA\Property(
                        property: "sppd_id",
                        type: "integer",
                        example: 71
                    ),

                    new OA\Property(
                        property: "transportations",
                        type: "array",
                        items: new OA\Items(
                            properties: [

                                new OA\Property(
                                    property: "id",
                                    type: "integer",
                                    example: 1
                                ),

                                new OA\Property(
                                    property: "actual_biaya",
                                    type: "number",
                                    format: "float",
                                    example: 850000
                                ),

                                new OA\Property(
                                    property: "keterangan_realisasi",
                                    type: "string",
                                    example: "Harga tiket aktual"
                                ),

                                new OA\Property(
                                    property: "lampiran",
                                    type: "array",
                                    items: new OA\Items(
                                        type: "string"
                                    ),
                                    example: [
                                        "https://domain.com/upload/tiket1.pdf",
                                        "https://domain.com/upload/tiket2.pdf"
                                    ]
                                )

                            ],
                            type: "object"
                        )
                    ),

                    new OA\Property(
                        property: "accommodations",
                        type: "array",
                        items: new OA\Items(
                            properties: [

                                new OA\Property(
                                    property: "id",
                                    type: "integer",
                                    example: 1
                                ),

                                new OA\Property(
                                    property: "actual_biaya",
                                    type: "number",
                                    format: "float",
                                    example: 1500000
                                ),

                                new OA\Property(
                                    property: "keterangan_realisasi",
                                    type: "string",
                                    example: "Biaya hotel aktual"
                                ),

                                new OA\Property(
                                    property: "lampiran",
                                    type: "array",
                                    items: new OA\Items(
                                        type: "string"
                                    ),
                                    example: [
                                        "https://domain.com/upload/hotel1.pdf",
                                        "https://domain.com/upload/hotel2.pdf"
                                    ]
                                )

                            ],
                            type: "object"
                        )
                    )

                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Realisasi berhasil disimpan",
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
                            example: "Realisasi berhasil disimpan"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation Error"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            )
        ]
    )]
        public function update(Request $request)
        {
            $sppd = TrSppd::where('id', $request->sppd_id)->first();

            if (!$sppd) {
                return response()->json([
                    'status' => false,
                    'message' => 'SPPD tidak ditemukan'
                ], 404);
            }

            if ($sppd->approval_status !== 'approved') {
                return response()->json([
                    'status' => false,
                    'message' => 'SPPD belum approved, tidak bisa input realisasi'
                ], 403);
            }
            
            $validated = $request->validate([

                'transportations' => 'array',

                'transportations.*.id' => 'required|exists:td_sppd_transportations,id',
                'transportations.*.actual_biaya' => 'required|numeric|min:0',
                'transportations.*.keterangan_realisasi' => 'nullable|string',
                'transportations.*.lampiran' => 'nullable|array',

                'accommodations' => 'array',

                'accommodations.*.id' => 'required|exists:td_sppd_accommodations,id',
                'accommodations.*.actual_biaya' => 'required|numeric|min:0',
                'accommodations.*.keterangan_realisasi' => 'nullable|string',
                'accommodations.*.lampiran' => 'nullable|array',
            ]);

            DB::beginTransaction();

            try {

                foreach ($validated['transportations'] ?? [] as $transport) {

                    TrSppdTransportasi::where('id', $transport['id'])
                        ->update([
                            'actual_biaya' => $transport['actual_biaya'],
                            'keterangan_realisasi' => $transport['keterangan_realisasi'] ?? null,
                            'lampiran' => $transport['lampiran'] ?? null,
                        ]);
                }

                foreach ($validated['accommodations'] ?? [] as $accommodation) {

                    TrSppdPenginapan::where('id', $accommodation['id'])
                        ->update([
                            'actual_biaya' => $accommodation['actual_biaya'],
                            'keterangan_realisasi' => $accommodation['keterangan_realisasi'] ?? null,
                            'lampiran' => $accommodation['lampiran'] ?? null,
                        ]);
                }

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Realisasi berhasil disimpan'
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