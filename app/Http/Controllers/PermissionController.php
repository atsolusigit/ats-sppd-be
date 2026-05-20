<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class PermissionController extends Controller
{
    #[OA\Get(
        path: "/api/permissions",
        tags: ["Permissions"],
        summary: "Get all permissions",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "name", type: "string"),
                                    new OA\Property(property: "slug", type: "string"),
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    // LIST
    public function index()
    {
        $data = DB::table('mst_permissions')->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    #[OA\Get(
        path: "/api/permissions/{id}",
        tags: ["Permissions"],
        summary: "Get permission detail",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
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
    // DETAIL
    public function show($id)
    {
        $data = DB::table('mst_permissions')->where('id', $id)->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Permission not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    #[OA\Post(
        path: "/api/permissions",
        tags: ["Permissions"],
        summary: "Create permission",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "slug"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "View Permission"),
                    new OA\Property(property: "slug", type: "string", example: "permission.view"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Permission created successfully")
                    ]
                )
            )
        ]
    )]

    // STORE
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:mst_permissions,slug',
        ]);

        DB::table('mst_permissions')->insert([
            'name' => $request->name,
            'slug' => $request->slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Permission created successfully'
        ]);
    }

    #[OA\Put(
        path: "/api/permissions/{id}",
        tags: ["Permissions"],
        summary: "Update permission",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                example: 1
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "slug"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Update Permission"),
                    new OA\Property(property: "slug", type: "string", example: "permission.update"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Updated successfully"
            ),
            new OA\Response(
                response: 404,
                description: "Not found"
            )
        ]
    )]

    // UPDATE
    public function update(Request $request, $id)
    {
        $permission = DB::table('mst_permissions')->where('id', $id)->first();

        if (!$permission) {
            return response()->json([
                'status' => false,
                'message' => 'Permission not found'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:mst_permissions,slug,' . $id,
        ]);

        DB::table('mst_permissions')
            ->where('id', $id)
            ->update([
                'name' => $request->name,
                'slug' => $request->slug,
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Permission updated successfully'
        ]);
    }

    #[OA\Delete(
        path: "/api/permissions/{id}",
        tags: ["Permissions"],
        summary: "Delete permission",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Deleted successfully"
            ),
            new OA\Response(
                response: 404,
                description: "Not found"
            )
        ]
    )]

    // DELETE
    public function destroy($id)
    {
        $permission = DB::table('mst_permissions')->where('id', $id)->first();

        if (!$permission) {
            return response()->json([
                'status' => false,
                'message' => 'Permission not found'
            ], 404);
        }

        // optional: hapus relasi dulu biar aman
        DB::table('role_permissions')
            ->where('permission_id', $id)
            ->delete();

        DB::table('mst_permissions')
            ->where('id', $id)
            ->delete();

        return response()->json([
            'status' => true,
            'message' => 'Permission deleted successfully'
        ]);
    }
}