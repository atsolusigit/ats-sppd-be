<?php

namespace App\Http\Controllers;

use App\Models\MstPage;
use Illuminate\Http\Request;
use App\Traits\HasDynamicFilter;
use App\Models\RolePage;
use OpenApi\Attributes as OA;

class PageController extends Controller
{
    use HasDynamicFilter;

    #[OA\Get(
        path: "/api/pages",
        tags: ["Page"],
        summary: "Get list pages",
        security: [["bearerAuth" => []]],
        parameters: [

            new OA\Parameter(
                name: "id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),

            new OA\Parameter(
                name: "name",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string")
            ),

            new OA\Parameter(
                name: "head_url",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string")
            ),

            new OA\Parameter(
                name: "is_web",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "boolean")
            ),

            new OA\Parameter(
                name: "is_mobile",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "boolean")
            ),

            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "boolean")
            ),

            new OA\Parameter(
                name: "search",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string")
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
        $query = MstPage::with([
            'roles',
            'users'
        ]);

        $query = $this->applyFilters(
            $query,
            $request,
            [
                'id',
                'name',
                'head_url',
                'is_web',
                'is_mobile',
                'status',
                'user_id',
                'created_by',
            ],
            [
                'name',
                'head_url',
            ]
        );

        $data = $query->paginate(
            $request->get('per_page', 10)
        );

        return response()->json([
            'status' => true,
            'message' => 'Pages fetched successfully',
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
        path: "/api/pages/{id}",
        tags: ["Page"],
        summary: "Get detail page",
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
                description: "Page not found"
            )
        ]
    )]

    public function show($id)
    {
        $data = MstPage::with(['roles', 'users'])->find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Page not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Page fetched successfully',
            'data' => $data
        ]);
    }


    #[OA\Post(
        path: "/api/pages",
        tags: ["Page"],
        summary: "Create page",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    "name",
                    "head_url"
                ],
                properties: [

                    new OA\Property(
                        property: "name",
                        type: "string",
                        example: "SPPD"
                    ),

                    new OA\Property(
                        property: "head_url",
                        type: "string",
                        example: "/sppd"
                    ),

                    new OA\Property(
                        property: "is_web",
                        type: "boolean",
                        example: true
                    ),

                    new OA\Property(
                        property: "is_mobile",
                        type: "boolean",
                        example: false
                    ),

                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: true
                    ),

                    new OA\Property(
                        property: "user_id",
                        type: "integer",
                        nullable: true,
                        example: 1
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Created"
            )
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'head_url' => 'required|string|max:255',
            'is_web' => 'boolean',
            'is_mobile' => 'boolean',
            'status' => 'boolean',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $data = MstPage::create([
            'name' => $request->name,
            'head_url' => $request->head_url,
            'is_web' => $request->is_web ?? true,
            'is_mobile' => $request->is_mobile ?? false,
            'status' => $request->status ?? true,
            'user_id' => $request->user_id,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Page created successfully',
            'data' => $data
        ], 201);
    }

    #[OA\Put(
        path: "/api/pages/{id}",
        tags: ["Page"],
        summary: "Update page",
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
                properties: [

                    new OA\Property(
                        property: "name",
                        type: "string"
                    ),

                    new OA\Property(
                        property: "head_url",
                        type: "string"
                    ),

                    new OA\Property(
                        property: "is_web",
                        type: "boolean"
                    ),

                    new OA\Property(
                        property: "is_mobile",
                        type: "boolean"
                    ),

                    new OA\Property(
                        property: "status",
                        type: "boolean"
                    ),

                    new OA\Property(
                        property: "user_id",
                        type: "integer",
                        nullable: true
                    ),
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
        $data = MstPage::find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Page not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'head_url' => 'sometimes|required|string|max:255',
            'is_web' => 'boolean',
            'is_mobile' => 'boolean',
            'status' => 'boolean',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $data->update([
            'name' => $request->name ?? $data->name,
            'head_url' => $request->head_url ?? $data->head_url,
            'is_web' => $request->is_web ?? $data->is_web,
            'is_mobile' => $request->is_mobile ?? $data->is_mobile,
            'status' => $request->status ?? $data->status,
            'user_id' => $request->user_id ?? $data->user_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Page updated successfully',
            'data' => $data->fresh()
        ]);
    }

    #[OA\Delete(
        path: "/api/pages/{id}",
        tags: ["Page"],
        summary: "Delete page",
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
                description: "Page not found"
            )
        ]
    )]

    public function destroy($id)
    {
        $data = MstPage::find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Page not found'
            ], 404);
        }

        $data->update([
            'deleted_by' => auth()->id()
        ]);

        $data->delete();

        return response()->json([
            'status' => true,
            'message' => 'Page deleted successfully'
        ]);
    }
}