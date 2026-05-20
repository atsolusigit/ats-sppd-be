<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MstJabatan;
use OpenApi\Attributes as OA;

class JabatanController extends Controller
{
    #[OA\Get(
        path: "/api/jabatans",
        tags: ["Jabatan"],
        summary: "Get all jabatan",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success"
            )
        ]
    )]
    public function index()
    {
        $data = MstJabatan::with([
            'department',
            'parent'
        ])->get();

        return response()->json([
            'status' => true,
            'message' => 'Jabatan fetched successfully',
            'data' => $data
        ]);
    }

    #[OA\Get(
        path: "/api/jabatans/{id}",
        tags: ["Jabatan"],
        summary: "Get detail jabatan",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success"
            )
        ]
    )]
    public function show($id)
    {
        $jabatan = MstJabatan::with([
            'department',
            'parent',
            'children',
            'approvalFlows'
        ])->find($id);

        if (!$jabatan) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Jabatan detail fetched successfully',
            'data' => $jabatan
        ]);
    }

    #[OA\Post(
        path: "/api/jabatans",
        tags: ["Jabatan"],
        summary: "Create jabatan",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "code", "department_id"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Manager IT"),
                    new OA\Property(property: "code", type: "string", example: "MGR_IT"),
                    new OA\Property(property: "level", type: "integer", example: 1),
                    new OA\Property(property: "department_id", type: "integer", example: 1),
                    new OA\Property(property: "parent_id", type: "integer", nullable: true, example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Created"
            )
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:mst_jabatans,name',
            'code' => 'required|string|unique:mst_jabatans,code',
            'department_id' => 'required|exists:mst_departments,id',
            'parent_id' => 'nullable|exists:mst_jabatans,id',
        ]);

        $jabatan = MstJabatan::create([
            'name' => $request->name,
            'code' => $request->code,
            'level' => $request->level,
            'department_id' => $request->department_id,
            'parent_id' => $request->parent_id,
            'status' => 1,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Jabatan created successfully',
            'data' => $jabatan
        ]);
    }

    #[OA\Put(
        path: "/api/jabatans/{id}",
        tags: ["Jabatan"],
        summary: "Update jabatan",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                example: 1
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "level", type: "integer"),
                    new OA\Property(property: "department_id", type: "integer"),
                    new OA\Property(property: "parent_id", type: "integer", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Updated"
            )
        ]
    )]
    public function update(Request $request, $id)
    {
        $jabatan = MstJabatan::find($id);

        if (!$jabatan) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan not found'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|unique:mst_jabatans,name,' . $id,
            'code' => 'required|string|unique:mst_jabatans,code,' . $id,
            'department_id' => 'required|exists:mst_departments,id',
            'parent_id' => 'nullable|exists:mst_jabatans,id',
        ]);

        $jabatan->update([
            'name' => $request->name,
            'code' => $request->code,
            'level' => $request->level,
            'department_id' => $request->department_id,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Jabatan updated successfully',
            'data' => $jabatan
        ]);
    }

    #[OA\Patch(
        path: "/api/jabatans/{id}/status",
        tags: ["Jabatan"],
        summary: "Toggle jabatan status",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Status updated"
            )
        ]
    )]
    public function updateStatus($id)
    {
        $jabatan = MstJabatan::find($id);

        if (!$jabatan) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan not found'
            ], 404);
        }

        $jabatan->status = !$jabatan->status;
        $jabatan->save();

        return response()->json([
            'status' => true,
            'message' => 'Jabatan status updated successfully'
        ]);
    }

    #[OA\Delete(
        path: "/api/jabatans/{id}",
        tags: ["Jabatan"],
        summary: "Delete jabatan",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                example: 1
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
        $jabatan = MstJabatan::find($id);

        if (!$jabatan) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan not found'
            ], 404);
        }

        $jabatan->delete();

        return response()->json([
            'status' => true,
            'message' => 'Jabatan deleted successfully'
        ]);
    }
}