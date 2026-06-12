<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MstJabatanApproval;
use OpenApi\Attributes as OA;
use App\Traits\HasDynamicFilter;

class JabatanApprovalController extends Controller
{
    use HasDynamicFilter;
    #[OA\Get(
        path: "/api/jabatan-approvals",
        tags: ["Jabatan Approval"],
        summary: "Get list jabatan approval",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the jabatan approval"
            ),

            new OA\Parameter(
                name: "approval_flow_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the approval flow [1, 2, 3]"
            ),

            new OA\Parameter(
                name: "approval_order",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "Order of the approval"
            ),

            new OA\Parameter(
                name: "approval_mode",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: [
                        "hierarchy",
                        "jabatan",
                        "department",
                        "role",
                        "user"
                    ]
                ),
                description: "Mode of the approval"
            ),

            new OA\Parameter(
                name: "target_level",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "Target level for the approval"
            ),

            new OA\Parameter(
                name: "target_jabatan_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the target jabatan"
            ),

            new OA\Parameter(
                name: "target_department_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the target department"
            ),

            new OA\Parameter(
                name: "target_role_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the target role"
            ),

            new OA\Parameter(
                name: "target_user_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the target user"
            ),

            new OA\Parameter(
                name: "is_required",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "boolean"),
                description: "Indicates if the approval is required"
            ),

            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "Current page for pagination"
            ),

            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "Number of items per page"
            ),

            new OA\Parameter(
                name: "sort_by",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Column to sort by"
            ),

            new OA\Parameter(
                name: "sort_dir",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["asc", "desc"]
                ),
                description: "Sort direction"
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
                            property: "message",
                            type: "string",
                            example: "Jabatan approvals fetched successfully"
                        ),

                        new OA\Property(
                            property: "pagination",
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
                            ],
                            type: "object"
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
                                        property: "approval_flow_id",
                                        type: "integer",
                                        example: 1
                                    ),

                                    new OA\Property(
                                        property: "approval_order",
                                        type: "integer",
                                        example: 1
                                    ),

                                    new OA\Property(
                                        property: "approval_mode",
                                        type: "string",
                                        example: "hierarchy"
                                    ),

                                    new OA\Property(
                                        property: "target_level",
                                        type: "integer",
                                        nullable: true,
                                        example: 2
                                    ),

                                    new OA\Property(
                                        property: "approval_key",
                                        type: "string",
                                        nullable: true,
                                        example: "finance_manager"
                                    ),

                                    new OA\Property(
                                        property: "target_jabatan_id",
                                        type: "integer",
                                        nullable: true,
                                        example: 3
                                    ),

                                    new OA\Property(
                                        property: "target_department_id",
                                        type: "integer",
                                        nullable: true,
                                        example: 1
                                    ),

                                    new OA\Property(
                                        property: "target_role_id",
                                        type: "integer",
                                        nullable: true,
                                        example: 1
                                    ),

                                    new OA\Property(
                                        property: "target_user_id",
                                        type: "integer",
                                        nullable: true,
                                        example: 10
                                    ),

                                    new OA\Property(
                                        property: "is_required",
                                        type: "boolean",
                                        example: true
                                    ),

                                    new OA\Property(
                                        property: "can_reject",
                                        type: "boolean",
                                        example: true
                                    ),

                                    new OA\Property(
                                        property: "can_revision",
                                        type: "boolean",
                                        example: true
                                    ),

                                    new OA\Property(
                                        property: "created_at",
                                        type: "string",
                                        example: "2026-05-21T10:00:00.000000Z"
                                    ),

                                    new OA\Property(
                                        property: "updated_at",
                                        type: "string",
                                        example: "2026-05-21T10:00:00.000000Z"
                                    ),

                                ]
                            )
                        )
                    ]
                )
            )
        ]
)]

    public function index(Request $request)
    {
        $query = MstJabatanApproval::with([
            'flow',
            'targetJabatan',
            'targetDepartment',
            'targetRole',
            'targetUser',
        ]);

        $query = $this->applyFilters(
            $query,
            $request,
            [
                'id',
                'approval_flow_id',
                'approval_order',
                'approval_mode',
                'target_level',
                'approval_key',
                'target_jabatan_id',
                'target_department_id',
                'target_role_id',
                'target_user_id',
                'is_required',
            ],
            []
        );

        $sortBy = $request->get('sort_by', 'approval_order');
        $sortDir = $request->get('sort_dir', 'asc');

        $query->orderBy($sortBy, $sortDir);
        $data = $query->paginate(
            $request->get('per_page', 10)
        );

        return response()->json([
            'status' => true,
            'message' => 'Jabatan approvals fetched successfully',

            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],

            'data' => $data->items(),
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
                description: "ID of the jabatan approval"
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
            'targetJabatan'
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
                    "approval_order",
                    'approval_key',
                    'target_jabatan_id',
                    'target_department_id',
                    'target_role_id',
                    'target_user_id',
                    'is_required',
                    'can_reject',
                    'can_revision',
                ],
                properties: [
                    new OA\Property(property: "jabatan_id", type: "integer", example: 1),
                    new OA\Property(property: "approval_flow_id", type: "integer", example: 1),
                    new OA\Property(property: "approval_order", type: "integer", example: 1),
                    new OA\Property(property: "approval_key", type: "string", nullable: true, example: "finance_manager"),
                    new OA\Property(property: "target_jabatan_id", type: "integer", nullable: true, example: 3),
                    new OA\Property(property: "target_department_id", type: "integer", nullable: true, example: 1),
                    new OA\Property(property: "target_role_id", type: "integer", nullable: true, example: 1),
                    new OA\Property(property: "target_user_id", type: "integer", nullable: true, example: 1),
                    new OA\Property(property: "is_required", type: "boolean", example: true),
                    new OA\Property(property: "can_reject", type: "boolean", example: true),
                    new OA\Property(property: "can_revision", type: "boolean", example: true),
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
            'approval_order' => 'required|integer|min:1',
            'approval_key' => 'nullable|string|max:255',
            'target_jabatan_id' => 'nullable|exists:mst_jabatans,id',
            'target_department_id' => 'nullable|exists:mst_departments,id',
            'target_role_id' => 'nullable|exists:mst_roles,id',
            'target_user_id' => 'nullable|exists:users,id',
            'is_required' => 'boolean',
            'can_reject' => 'boolean',
            'can_revision' => 'boolean',
        ]);

        $exists = MstJabatanApproval::where('approval_flow_id', $request->approval_flow_id)
            ->where('jabatan_id', $request->jabatan_id)
            ->where('approval_order', $request->approval_order)
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
            'approval_order' => $request->approval_order,
            'approval_key' => $request->approval_key,
            'target_jabatan_id' => $request->target_jabatan_id,
            'target_department_id' => $request->target_department_id,
            'target_role_id' => $request->target_role_id,
            'target_user_id' => $request->target_user_id,
            'is_required' => $request->is_required,
            'can_reject' => $request->can_reject,
            'can_revision' => $request->can_revision,
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
                    new OA\Property(property: "approval_order", type: "integer"),
                    new OA\Property(property: "approval_key", type: "string", nullable: true),
                    new OA\Property(property: "target_jabatan_id", type: "integer", nullable: true),
                    new OA\Property(property: "target_department_id", type: "integer", nullable: true),
                    new OA\Property(property: "target_role_id", type: "integer", nullable: true),
                    new OA\Property(property: "target_user_id", type: "integer", nullable: true),
                    new OA\Property(property: "is_required", type: "boolean"),
                    new OA\Property(property: "can_reject", type: "boolean"),
                    new OA\Property(property: "can_revision", type: "boolean"),
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
            'approval_order' => 'required|integer|min:1',
            'approval_key' => 'nullable|string|max:255',
            'target_jabatan_id' => 'nullable|exists:mst_jabatans,id',
            'target_department_id' => 'nullable|exists:mst_departments,id',
            'target_role_id' => 'nullable|exists:mst_roles,id',
            'target_user_id' => 'nullable|exists:users,id',
            'is_required' => 'boolean',
            'can_reject' => 'boolean',
            'can_revision' => 'boolean',
        ]);

        $data->update([
            'jabatan_id' => $request->jabatan_id,
            'approval_flow_id' => $request->approval_flow_id,
            'approval_order' => $request->approval_order,
            'approval_key' => $request->approval_key,
            'target_jabatan_id' => $request->target_jabatan_id,
            'target_department_id' => $request->target_department_id,
            'target_role_id' => $request->target_role_id,
            'target_user_id' => $request->target_user_id,
            'is_required' => $request->is_required,
            'can_reject' => $request->can_reject,
            'can_revision' => $request->can_revision,
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