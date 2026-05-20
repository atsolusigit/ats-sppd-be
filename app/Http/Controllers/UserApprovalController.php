<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class UserApprovalController extends Controller
{
    // =========================
    // GET PENDING USERS
    // =========================
    #[OA\Get(
        path: "/api/admin/users/pending",
        tags: ["User Approval"],
        summary: "Get all pending users",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Pending users fetched"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "string", example: "ENC_ID"),
                                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                                    new OA\Property(property: "username", type: "string", example: "john"),
                                    new OA\Property(property: "email", type: "string", example: "john@mail.com"),
                                    new OA\Property(property: "role_id", type: "integer", example: 3),
                                    new OA\Property(property: "created_at", type: "string", example: "2026-05-18 10:00:00"),
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function pending()
    {
        $users = User::where('status', 0)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => encrypt_decrypt_db('dec', $user->name, $user->id),
                    'username' => encrypt_decrypt_db('dec', $user->username, $user->id),
                    'email' => encrypt_decrypt_db('dec', $user->email, $user->id),
                    'role_id' => $user->role_id,
                    'created_at' => $user->created_at,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Pending users fetched',
            'data' => $users
        ]);
    }

    // =========================
    // APPROVE USER
    // =========================
    #[OA\Post(
        path: "/api/admin/users/{id}/approve",
        tags: ["User Approval"],
        summary: "Approve user",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "User ID",
                example: 5
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["role_id", "department_id", "jabatan_id"],
                properties: [
                    new OA\Property(property: "role_id", type: "integer", example: 2),
                    new OA\Property(property: "department_id", type: "integer", example: 1),
                    new OA\Property(property: "jabatan_id", type: "integer", example: 6),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Success"
            )
        ]
    )]
    public function approve(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $userId = $id;
            $user = User::findOrFail($userId);

            $user->status = 1;
            $user->role_id = $request->role_id ?? $user->role_id;
            $user->department_id = $request->department_id ?? $user->department_id;
            $user->jabatan_id = $request->jabatan_id;
            $user->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'User approved & assigned successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // =========================
    // REJECT USER
    // =========================
    #[OA\Post(
        path: "/api/admin/users/{id}/reject",
        tags: ["User Approval"],
        summary: "Reject user",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "User ID",
                example: 5
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "reason", type: "string", example: "Data tidak valid")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "User rejected successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "User rejected successfully")
                    ]
                )
            )
        ]
    )]
    public function reject($id)
    {
        DB::beginTransaction();

        try {
            $userId = $id;
            $user = User::findOrFail($userId);

            $user->status = 2;
            $user->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'User rejected successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}