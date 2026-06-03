<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MstApprovalFlow;
use OpenApi\Attributes as OA;

class ApprovalFlowController extends Controller
{
    #[OA\Get(
        path: "/api/approval-flows",
        tags: ["Approval Flow"],
        summary: "Get all approval flows",
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
        $data = MstApprovalFlow::with([
            'department',
            'jabatanApprovals'
        ])->get();

        return response()->json([
            'status' => true,
            'message' => 'Approval flows fetched successfully',
            'data' => $data
        ]);
    }

    #[OA\Get(
        path: "/api/approval-flows/{id}",
        tags: ["Approval Flow"],
        summary: "Get detail approval flow",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the approval flow"
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
        $flow = MstApprovalFlow::with([
            'department',
            'jabatanApprovals.targetJabatan',
        ])->find($id);

        if (!$flow) {
            return response()->json([
                'status' => false,
                'message' => 'Approval flow not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Approval flow fetched successfully',
            'data' => $flow
        ]);
    }

    #[OA\Post(
        path: "/api/approval-flows",
        tags: ["Approval Flow"],
        summary: "Create approval flow",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "module"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "SPPD Approval"),
                    new OA\Property(property: "module", type: "string", example: "sppd"),
                    new OA\Property(property: "department_id", type: "integer", nullable: true, example: 1),
                    new OA\Property(property: "description", type: "string", example: "Approval flow for SPPD"),
                    new OA\Property(property: "is_active", type: "boolean", example: true),
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
            'name' => 'required|string|unique:mst_approval_flows,name',
            'module' => 'required|string',
            'department_id' => 'nullable|exists:mst_departments,id',
            'description' => 'nullable|string',
        ]);

        $flow = MstApprovalFlow::create([
            'name' => $request->name,
            'module' => $request->module,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'is_active' => $request->is_active ?? 1,
            'status' => 1,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Approval flow created successfully',
            'data' => $flow
        ]);
    }

    #[OA\Put(
        path: "/api/approval-flows/{id}",
        tags: ["Approval Flow"],
        summary: "Update approval flow",
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
                    new OA\Property(property: "module", type: "string"),
                    new OA\Property(property: "department_id", type: "integer", nullable: true),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "is_active", type: "boolean"),
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
        $flow = MstApprovalFlow::find($id);

        if (!$flow) {
            return response()->json([
                'status' => false,
                'message' => 'Approval flow not found'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|unique:mst_approval_flows,name,' . $id,
            'module' => 'required|string',
            'department_id' => 'nullable|exists:mst_departments,id',
        ]);

        $flow->update([
            'name' => $request->name,
            'module' => $request->module,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'is_active' => $request->is_active,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Approval flow updated successfully',
            'data' => $flow
        ]);
    }

    #[OA\Patch(
        path: "/api/approval-flows/{id}/status",
        tags: ["Approval Flow"],
        summary: "Toggle approval flow status",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the approval flow"
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
        $flow = MstApprovalFlow::find($id);

        if (!$flow) {
            return response()->json([
                'status' => false,
                'message' => 'Approval flow not found'
            ], 404);
        }

        $flow->status = !$flow->status;
        $flow->save();

        return response()->json([
            'status' => true,
            'message' => 'Approval flow status updated successfully'
        ]);
    }

    #[OA\Delete(
        path: "/api/approval-flows/{id}",
        tags: ["Approval Flow"],
        summary: "Delete approval flow",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the approval flow"
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
        $flow = MstApprovalFlow::find($id);

        if (!$flow) {
            return response()->json([
                'status' => false,
                'message' => 'Approval flow not found'
            ], 404);
        }

        $flow->delete();

        return response()->json([
            'status' => true,
            'message' => 'Approval flow deleted successfully'
        ]);
    }
}