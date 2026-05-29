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
        $query = User::query()->select([
            'id',
            'name',
            'email',
            'username',
            'status',
            'role_id',
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
                'role_id'
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

                'role_id' => $item->role_id,

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
}