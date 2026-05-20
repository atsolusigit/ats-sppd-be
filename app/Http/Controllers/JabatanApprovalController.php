<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MstJabatanApproval;
use OpenApi\Attributes as OA;

class JabatanApprovalController extends Controller
{
    #[OA\Get(
        path: "/api/jabatan-approvals",
        tags: ["Jabatan Approval"],
        summary: "Get all jabatan approvals",
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
        $data = MstJabatanApproval::with([
            'flow',
            'jabatan'
        ])
        ->ordered()
        ->get();

        return response()->json([
            'status' => true,
            'message' => 'Jabatan approvals fetched successfully',
            'data' => $data
        ]);
    }

    #[OA\Get(
        path: "/api/jabatan-approvals/{id}",
        tags: ["Jabatan Approval"],
        summary: "Get detail jabatan approval",
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
            ),
            new OA\Response(
                response: 404,
                description: "Not found"
            )
        ]
    )]
    public function show($id)
    {
        $data = MstJabatanApproval::with([
            'flow',
            'jabatan'
        ])->find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan approval not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Jabatan approval fetched successfully',
            'data' => $data
        ]);
    }

    #[OA\Post(
        path: "/api/jabatan-approvals",
        tags: ["Jabatan Approval"],
        summary: "Create jabatan approval",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    "jabatan_id",
                    "approval_flow_id",
                    "approval_level"
                ],
                properties: [
                    new OA\Property(property: "jabatan_id", type: "integer", example: 1),
                    new OA\Property(property: "approval_flow_id", type: "integer", example: 1),
                    new OA\Property(property: "approval_level", type: "integer", example: 1),
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
            'jabatan_id' => 'required|exists:mst_jabatans,id',
            'approval_flow_id' => 'required|exists:mst_approval_flows,id',
            'approval_level' => 'required|integer|min:1',
        ]);

        $exists = MstJabatanApproval::where('approval_flow_id', $request->approval_flow_id)
            ->where('jabatan_id', $request->jabatan_id)
            ->where('approval_level', $request->approval_level)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Approval mapping already exists'
            ], 422);
        }

        $data = MstJabatanApproval::create([
            'jabatan_id' => $request->jabatan_id,
            'approval_flow_id' => $request->approval_flow_id,
            'approval_level' => $request->approval_level,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Jabatan approval created successfully',
            'data' => $data
        ]);
    }

    #[OA\Put(
        path: "/api/jabatan-approvals/{id}",
        tags: ["Jabatan Approval"],
        summary: "Update jabatan approval",
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
                    new OA\Property(property: "jabatan_id", type: "integer"),
                    new OA\Property(property: "approval_flow_id", type: "integer"),
                    new OA\Property(property: "approval_level", type: "integer"),
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
        $data = MstJabatanApproval::find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan approval not found'
            ], 404);
        }

        $request->validate([
            'jabatan_id' => 'required|exists:mst_jabatans,id',
            'approval_flow_id' => 'required|exists:mst_approval_flows,id',
            'approval_level' => 'required|integer|min:1',
        ]);

        $data->update([
            'jabatan_id' => $request->jabatan_id,
            'approval_flow_id' => $request->approval_flow_id,
            'approval_level' => $request->approval_level,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Jabatan approval updated successfully',
            'data' => $data
        ]);
    }

    #[OA\Delete(
        path: "/api/jabatan-approvals/{id}",
        tags: ["Jabatan Approval"],
        summary: "Delete jabatan approval",
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
        $data = MstJabatanApproval::find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan approval not found'
            ], 404);
        }

        $data->delete();

        return response()->json([
            'status' => true,
            'message' => 'Jabatan approval deleted successfully'
        ]);
    }
}