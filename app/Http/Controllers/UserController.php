<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\HasDynamicFilter;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    use HasDynamicFilter;

    #[OA\Get(
        path: "/api/users",
        tags: ["Users"],
        summary: "Get users list",
        security: [["bearerAuth" => []]],

        parameters: [

            new OA\Parameter(
                name: "id",
                in: "query",
                required: false,
                description: "Filter by user id",
                schema: new OA\Schema(type: "integer", example: 1)
            ),

            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Filter by status",
                schema: new OA\Schema(type: "integer", example: 1)
            ),

            new OA\Parameter(
                name: "role_id",
                in: "query",
                required: false,
                description: "Filter by role id",
                schema: new OA\Schema(type: "integer", example: 5)
            ),

            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                description: "Pagination page",
                schema: new OA\Schema(type: "integer", example: 1)
            ),

            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                description: "Items per page",
                schema: new OA\Schema(type: "integer", example: 10)
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
                            property: "pagination",
                            type: "object",
                            properties: [

                                new OA\Property(
                                    property: "current_page",
                                    type: "integer",
                                    example: 1
                                ),

                                new OA\Property(
                                    property: "last_page",
                                    type: "integer",
                                    example: 1
                                ),

                                new OA\Property(
                                    property: "per_page",
                                    type: "integer",
                                    example: 10
                                ),

                                new OA\Property(
                                    property: "total",
                                    type: "integer",
                                    example: 3
                                ),
                            ]
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
                                        property: "name",
                                        type: "string",
                                        example: "John Doe"
                                    ),

                                    new OA\Property(
                                        property: "email",
                                        type: "string",
                                        example: "john@gmail.com"
                                    ),

                                    new OA\Property(
                                        property: "username",
                                        type: "string",
                                        example: "john_doe"
                                    ),

                                    new OA\Property(
                                        property: "status",
                                        type: "integer",
                                        example: 1
                                    ),

                                    new OA\Property(
                                        property: "role_id",
                                        type: "integer",
                                        example: 5
                                    ),

                                    new OA\Property(
                                        property: "created_at",
                                        type: "string",
                                        format: "date-time",
                                        example: "2026-05-19T02:24:41.000000Z"
                                    ),
                                ]
                            )
                        )
                    ]
                )
            ),

            new OA\Response(
                response: 401,
                description: "Unauthorized"
            )
        ]
    )]

    public function index(Request $request)
    {
        $query = User::query()
        ->with(['role:id,name,status', 'jabatan:id,name,status'])
        ->select([
            'id',
            'name',
            'email',
            'username',
            'status',
            'role_id',
            'jabatan_id',
            'created_at'
        ]);

        /*
        |--------------------------------------------------------------------------
        | FILTERS
        |--------------------------------------------------------------------------
        */

        $query = $this->applyFilters(
            $query,
            $request,
            [
                'id',
                'status',
                'role_id',
                'jabatan_id'   
            ],
            []
        );

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        $data = $query->paginate(
            $request->get('per_page', 10)
        );

        /*
        |--------------------------------------------------------------------------
        | FORMAT RESPONSE
        |--------------------------------------------------------------------------
        */

        $collection = $data->getCollection()->map(function ($item) {

            return [

                'id' => $item->id,

                // gunakan accessor model
                'name' => $item->name_decrypted,

                'email' => $item->email_decrypted,

                'username' => $item->username_decrypted,

                'status' => $item->status,

                'jabatan' => $item->jabatan ? [
                    'id' => $item->jabatan->id,
                    'name' => $item->jabatan->name,
                    'status' => $item->jabatan->status,
                ] : null,

                'role' => $item->role ? [
                        'id' => $item->role->id,
                        'name' => $item->role->name,
                        'status' => $item->role->status,
                    ] : null,

                'created_at' => $item->created_at,
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status' => true,

            'pagination' => [

                'current_page' => $data->currentPage(),

                'last_page' => $data->lastPage(),

                'per_page' => $data->perPage(),

                'total' => $data->total(),
            ],

            'data' => $collection
        ]);
    }

    #[OA\Get(
        path: "/api/users/{id}",
        tags: ["Users"],
        summary: "Get user detail",
        security: [["bearerAuth" => []]],

        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],

        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),

                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer"),
                                new OA\Property(property: "name", type: "string"),
                                new OA\Property(property: "email", type: "string"),
                                new OA\Property(property: "username", type: "string"),
                                new OA\Property(property: "status", type: "integer"),

                                new OA\Property(property: "role", type: "object"),
                                new OA\Property(property: "jabatan", type: "object"),

                                new OA\Property(property: "created_at", type: "string", format: "date-time"),
                            ]
                        )
                    ]
                )
            ),

            new OA\Response(
                response: 404,
                description: "User not found"
            )
        ]
    )]

    public function show($id)
    {
        $user = User::with(['role:id,name,status', 'jabatan:id,name,status'])
            ->find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name_decrypted,
                'email' => $user->email_decrypted,
                'username' => $user->username_decrypted,
                'status' => $user->status,

                'role' => $user->role,
                'jabatan' => $user->jabatan,

                'created_at' => $user->created_at,
            ]
        ]);
    }


    #[OA\Put(
        path: "/api/users/{id}",
        tags: ["Users"],
        summary: "Update user",
        security: [["bearerAuth" => []]],

        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],

        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Updated"),
                    new OA\Property(property: "email", type: "string", example: "john2@gmail.com"),
                    new OA\Property(property: "username", type: "string", example: "john_updated"),
                    new OA\Property(property: "password", type: "string", example: "newpass123"),
                    new OA\Property(property: "status", type: "integer", example: 1),
                    new OA\Property(property: "role_id", type: "integer", example: 2),
                    new OA\Property(property: "jabatan_id", type: "integer", example: 3),
                ]
            )
        ),

        responses: [
            new OA\Response(
                response: 200,
                description: "User updated successfully"
            ),
            new OA\Response(
                response: 404,
                description: "User not found"
            )
        ]
    )]
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email',
            'username' => 'sometimes|string',
            'password' => 'sometimes|string|min:6',
            'status' => 'sometimes|integer',
            'role_id' => 'sometimes|integer',
            'jabatan_id' => 'sometimes|integer',
        ]);

        if ($request->has('name')) {
            $user->name = encrypt_decrypt_db('enc', $request->name);
        }

        if ($request->has('email')) {
            $user->email = encrypt_decrypt_db('enc', $request->email);
        }

        if ($request->has('username')) {
            $user->username = encrypt_decrypt_db('enc', $request->username);
        }

        if ($request->has('password')) {
            $user->password = bcrypt($request->password);
        }

        if ($request->has('status')) {
            $user->status = $request->status;
        }

        if ($request->has('role_id')) {
            $user->role_id = $request->role_id;
        }

        if ($request->has('jabatan_id')) {
            $user->jabatan_id = $request->jabatan_id;
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User updated successfully'
        ]);
    }

    #[OA\Delete(
        path: "/api/users/{id}",
        tags: ["Users"],
        summary: "Delete user",
        security: [["bearerAuth" => []]],

        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],

        responses: [
            new OA\Response(
                response: 200,
                description: "User deleted successfully"
            ),
            new OA\Response(
                response: 404,
                description: "User not found"
            )
        ]
    )]

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}