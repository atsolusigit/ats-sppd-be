<?php

namespace App\Http\Controllers;

use App\Models\RolePage;
use Illuminate\Http\Request;
use App\Traits\HasDynamicFilter;
use OpenApi\Attributes as OA;
use App\Models\MstPage;
use App\Models\MstRole;

class RolePageController extends Controller
{
    use HasDynamicFilter;

    #[OA\Get(
        path: "/api/role-pages",
        tags: ["Role Page"],
        summary: "Get list role page mappings",
        security: [["bearerAuth" => []]],
        parameters: [

            new OA\Parameter(
                name: "id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),

            new OA\Parameter(
                name: "role_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),

            new OA\Parameter(
                name: "page_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),

            new OA\Parameter(
                name: "access",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "boolean")
            ),

            new OA\Parameter(
                name: "sort_by",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string")
            ),

            new OA\Parameter(
                name: "sort_order",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["asc", "desc"]
                )
            ),

            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),

            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),
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
        $query = RolePage::with([
            'role',
            'page'
        ]);

        $query = $this->applyFilters(
            $query,
            $request,
            [
                'id',
                'role_id',
                'page_id',
                'access',
                'created_by',
            ],
            []
        );

        $data = $query->paginate(
            $request->get('per_page', 10)
        );

        return response()->json([
            'status' => true,
            'message' => 'Role pages fetched successfully',
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
        path: "/api/role-pages/{id}",
        tags: ["Role Page"],
        summary: "Get role page detail",
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
                description: "Role page not found"
            )
        ]
    )]

    public function show($id)
    {
        $data = RolePage::with(['role', 'page'])->find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Role page not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Role page fetched successfully',
            'data' => $data
        ]);
    }


    #[OA\Post(
        path: "/api/role-pages",
        tags: ["Role Page"],
        summary: "Create role page mapping",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    "role_id",
                    "page_id",
                    "access"
                ],
                properties: [

                    new OA\Property(
                        property: "role_id",
                        type: "integer",
                        example: 1
                    ),

                    new OA\Property(
                        property: "page_id",
                        type: "integer",
                        example: 5
                    ),

                    new OA\Property(
                        property: "access",
                        type: "boolean",
                        example: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Created"
            ),
            new OA\Response(
                response: 422,
                description: "Role page already exists"
            )
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:mst_roles,id',
            'page_id' => 'required|exists:mst_page,id',
            'access' => 'required|boolean',
        ]);

        $exists = RolePage::where('role_id', $validated['role_id'])
            ->where('page_id', $validated['page_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Role page already exists'
            ], 422);
        }

        $validated['created_by'] = auth()->id();

        $data = RolePage::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Role page created successfully',
            'data' => $data->load(['role', 'page'])
        ], 201);
    }

    #[OA\Put(
        path: "/api/role-pages/{id}",
        tags: ["Role Page"],
        summary: "Update role page access",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["access"],
                properties: [
                    new OA\Property(
                        property: "access",
                        type: "boolean",
                        example: false
                    )
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
        $data = RolePage::find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Role page not found'
            ], 404);
        }

        $validated = $request->validate([
            'access' => 'required|boolean',
        ]);

        $data->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Role page updated successfully',
            'data' => $data->fresh()->load(['role', 'page'])
        ]);
    }

    #[OA\Delete(
        path: "/api/role-pages/{id}",
        tags: ["Role Page"],
        summary: "Delete role page mapping",
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
            ),
            new OA\Response(
                response: 404,
                description: "Role page not found"
            )
        ]
    )]
    public function destroy($id)
    {
        $data = RolePage::find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Role page not found'
            ], 404);
        }

        $data->delete();

        return response()->json([
            'status' => true,
            'message' => 'Role page deleted successfully'
        ]);
    }
}