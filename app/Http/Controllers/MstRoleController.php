<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MstRole;
use OpenApi\Attributes as OA;

class MstRoleController extends Controller
{
    #[OA\Get(
        path: "/api/roles",
        tags: ["Roles"],
        summary: "Get all roles",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Roles fetched successfully"
            )
        ]
    )]
    
    public function index()
    {
        $roles = MstRole::with(['permissions', 'pages'])->get();

        return response()->json([
            'status' => true,
            'message' => 'Roles fetched successfully',
            'data' => $roles
        ]);
    }

     #[OA\Get(
        path: "/api/roles/{id}",
        tags: ["Roles"],
        summary: "Get detail role",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID of the role"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Role fetched successfully"
            ),
            new OA\Response(
                response: 404,
                description: "Role not found"
            )
        ]
    )]

    public function show($id)
    {
        $role = MstRole::with(['permissions', 'pages'])
            ->find($id);

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Role fetched successfully',
            'data' => $role
        ]);
    }

    #[OA\Post(
        path: "/api/roles",
        tags: ["Roles"],
        summary: "Create role",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Super Admin"),
                    new OA\Property(property: "code", type: "string", example: "SUPER_ADMIN"),
                    new OA\Property(property: "description", type: "string", example: "Full access role"),
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(property: "is_default", type: "boolean", example: false),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Role created successfully"
            )
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:mst_roles,name',
            'code' => 'nullable|string|unique:mst_roles,code',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $role = MstRole::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'status' => $request->status ?? true,
            'is_default' => $request->is_default ?? false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Role created successfully',
            'data' => $role
        ]);
    }

    #[OA\Put(
        path: "/api/roles/{id}",
        tags: ["Roles"],
        summary: "Update role",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID of the role"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Manager"),
                    new OA\Property(property: "code", type: "string", example: "MANAGER"),
                    new OA\Property(property: "description", type: "string", example: "Manager role"),
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(property: "is_default", type: "boolean", example: false),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Role updated successfully"
            ),
            new OA\Response(
                response: 404,
                description: "Role not found"
            )
        ]
    )]
    public function update(Request $request, $id)
    {
        $role = MstRole::find($id);

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|unique:mst_roles,name,' . $id,
            'code' => 'nullable|string|unique:mst_roles,code,' . $id,
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $role->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'status' => $request->status,
            'is_default' => $request->is_default,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Role updated successfully',
            'data' => $role
        ]);
    }

    #[OA\Patch(
        path: "/api/roles/{id}/status",
        tags: ["Roles"],
        summary: "Toggle role status",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID of the role"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Role status updated successfully"
            ),
            new OA\Response(
                response: 404,
                description: "Role not found"
            )
        ]
    )]

    public function updateStatus($id)
    {
        $role = MstRole::find($id);

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $role->update([
            'status' => !$role->status
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Role status updated successfully',
            'data' => $role
        ]);
    }

    #[OA\Delete(
        path: "/api/roles/{id}",
        tags: ["Roles"],
        summary: "Delete role",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID of the role"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Role deleted successfully"
            ),
            new OA\Response(
                response: 404,
                description: "Role not found"
            )
        ]
    )]
    public function destroy($id)
    {
        $role = MstRole::find($id);

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $role->delete();

        return response()->json([
            'status' => true,
            'message' => 'Role deleted successfully'
        ]);
    }

    #[OA\Post(
        path: "/api/roles/{id}/permissions",
        tags: ["Roles"],
        summary: "Assign permissions to role",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID of the role"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["permission_ids"],
                properties: [
                    new OA\Property(
                        property: "permission_ids",
                        type: "array",
                        items: new OA\Items(type: "integer"),
                        example: [1,2,3]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Permissions assigned successfully"
            )
        ]
    )]
    public function assignPermissions(Request $request, $id)
    {
        $role = MstRole::find($id);

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:mst_permissions,id'
        ]);

        $role->permissions()->sync($request->permission_ids);

        return response()->json([
            'status' => true,
            'message' => 'Permissions assigned successfully'
        ]);
    }

    #[OA\Get(
        path: "/api/roles/{id}/permissions",
        tags: ["Roles"],
        summary: "Get permissions by role",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Role ID",
                schema: new OA\Schema(type: "integer"),
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Permissions fetched successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Permissions fetched successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(
                                    property: "role",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "Super Admin"),
                                    ],
                                    type: "object"
                                ),
                                new OA\Property(
                                    property: "permissions",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "View User"),
                                            new OA\Property(property: "slug", type: "string", example: "user.view"),
                                        ],
                                        type: "object"
                                    )
                                )
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Role not found"
            )
        ]
    )]

    public function getPermissions($id)
    {
        $role = MstRole::with(['permissions'])->find($id);

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Permissions fetched successfully',
            'data' => [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                ],
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'slug' => $permission->slug,
                    ];
                })
            ]
        ]);
    }
}