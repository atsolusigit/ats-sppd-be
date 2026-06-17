<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\TrAttachment;
use App\Models\TrSppd;
use OpenApi\Attributes as OA;

class AttachmentController extends Controller
{
    /**
     * STORE attachment (file / link)
     */

    #[OA\Post(
        path: "/api/attachments",
        tags: ["Attachments"],
        summary: "Upload file atau link attachment (SPPD / Realisasi / Report)",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["sppd_id", "module", "type"],
                    properties: [

                        new OA\Property(
                            property: "sppd_id",
                            type: "integer",
                            example: 10
                        ),

                        new OA\Property(
                            property: "reference_id",
                            type: "integer",
                            example: 55,
                            nullable: true
                        ),

                        new OA\Property(
                            property: "module",
                            type: "string",
                            enum: ["sppd", "realisasi", "report"],
                            example: "realisasi"
                        ),

                        new OA\Property(
                            property: "category",
                            type: "string",
                            enum: ["transport", "accommodation", "general"],
                            example: "transport"
                        ),

                        new OA\Property(
                            property: "type",
                            type: "string",
                            enum: ["file", "link"],
                            example: "file"
                        ),

                        new OA\Property(
                            property: "file",
                            type: "string",
                            format: "binary"
                        ),

                        new OA\Property(
                            property: "url",
                            type: "string",
                            example: "https://drive.google.com/file/d/xxx/view"
                        ),

                        new OA\Property(
                            property: "file_name",
                            type: "string",
                            example: "ticket.pdf"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Attachment created"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error"
            )
        ]
    )]
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'sppd_id' => 'required|exists:tr_sppd,id',

                'reference_id' => 'nullable|integer',

                'module' => 'required|in:sppd,realisasi,report',

                'category' => 'nullable|in:transport,accommodation,general',

                'type' => 'required|in:file,link',

                'file' => 'required_if:type,file|file|max:10240',

                'url' => 'required_if:type,link|nullable|url',

                'file_name' => 'nullable|string|max:255',
            ]);

            $sppd = TrSppd::findOrFail($request->sppd_id);

            $attachment = new TrAttachment();
            $attachment->sppd_id = $sppd->id;
            $attachment->reference_id = $request->reference_id;
            $attachment->module = $request->module;
            $attachment->category = $request->category;
            $attachment->type = $request->type;
            $attachment->created_by = auth()->id();

            if ($request->type === 'file') {

                $file = $request->file('file');

                // path berdasarkan module (rapi & scalable)
                $path = $file->store("attachments/{$request->module}", 'public');

                $attachment->file_name = $file->getClientOriginalName();
                $attachment->file_path = $path;

            } else {

                $attachment->file_name = $request->file_name;
                $attachment->url = $request->url;
            }

            $attachment->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Attachment berhasil ditambahkan',
                'data' => $attachment
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET LIST ATTACHMENT BY SPPD
     */

    #[OA\Get(
        path: "/api/attachments",
        tags: ["Attachments"],
        summary: "List attachments",
        security: [["bearerAuth" => []]],
        parameters: [

            new OA\Parameter(
                name: "sppd_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),

            new OA\Parameter(
                name: "module",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["sppd", "realisasi", "report"]
                )
            ),

            new OA\Parameter(
                name: "category",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["transport", "accommodation", "general"]
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
        $query = TrAttachment::query();

        if ($request->sppd_id) {
            $query->where('sppd_id', $request->sppd_id);
        }

        if ($request->module) {
            $query->where('module', $request->module);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        return response()->json([
            'status' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * DETAIL
     */

    #[OA\Get(
        path: "/api/attachments/{id}",
        tags: ["Attachments"],
        summary: "Get detail attachment",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success"
            ),
            new OA\Response(
                response: 404,
                description: "Not found"
            )
    ]
)]
    public function show($id)
    {
        $data = TrAttachment::find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Attachment not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * DOWNLOAD FILE
     */

    #[OA\Get(
        path: "/api/attachments/{id}/download",
        tags: ["Attachments"],
        summary: "Download file attachment",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "File download"
            ),
            new OA\Response(
                response: 400,
                description: "Not a file"
            )
        ]
)]
    public function download($id)
    {
        $attachment = TrAttachment::findOrFail($id);

        if ($attachment->type !== 'file') {
            return response()->json([
                'status' => false,
                'message' => 'This attachment is not a file'
            ], 400);
        }

        return Storage::disk('public')->download(
            $attachment->file_path,
            $attachment->file_name
        );
    }

    /**
     * DELETE
     */

    #[OA\Delete(
        path: "/api/attachments/{id}",
        tags: ["Attachments"],
        summary: "Delete attachment",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Deleted"
            )
        ]
    )]
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $attachment = TrAttachment::findOrFail($id);

            if ($attachment->type === 'file' && $attachment->file_path) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $attachment->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Attachment deleted'
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