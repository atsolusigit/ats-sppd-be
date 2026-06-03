<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DepartmentController extends Controller
{
    #[OA\Get(
        path: "/api/departments",
        tags: ["Departments"],
        summary: "Get all departments",
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
        $data = DB::table('mst_departments')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Departments fetched successfully',
            'data' => $data
        ]);
    }

    #[OA\Post(
        path: "/api/departments",
        tags: ["Departments"],
        summary: "Create department",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "code"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Human Resource"),
                    new OA\Property(property: "code", type: "string", example: "HR"),
                    new OA\Property(property: "description", type: "string", example: "Human Resource Department"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Department created"
            )
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:mst_departments,name',
            'code' => 'required|string|unique:mst_departments,code',
        ]);

        DB::table('mst_departments')->insert([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'status' => 1,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Department created successfully'
        ]);
    }

    #[OA\Put(
        path: "/api/departments/{id}",
        tags: ["Departments"],
        summary: "Update department",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the department"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Finance"),
                    new OA\Property(property: "code", type: "string", example: "FIN"),
                    new OA\Property(property: "description", type: "string", example: "Finance Department"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Department updated"
            )
        ]
    )]
    public function update(Request $request, $id)
    {
        $department = DB::table('mst_departments')->where('id', $id)->first();

        if (!$department) {
            return response()->json([
                'status' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|unique:mst_departments,name,' . $id,
            'code' => 'required|string|unique:mst_departments,code,' . $id,
        ]);

        DB::table('mst_departments')
            ->where('id', $id)
            ->update([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Department updated successfully'
        ]);
    }

    #[OA\Patch(
        path: "/api/departments/{id}/status",
        tags: ["Departments"],
        summary: "Toggle department status",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the department"
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
        $department = DB::table('mst_departments')
            ->where('id', $id)
            ->first();

        if (!$department) {
            return response()->json([
                'status' => false,
                'message' => 'Department not found'
            ], 404);
        }

        DB::table('mst_departments')
            ->where('id', $id)
            ->update([
                'status' => !$department->status,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Department status updated successfully'
        ]);
    }

    #[OA\Delete(
        path: "/api/departments/{id}",
        tags: ["Departments"],
        summary: "Delete department",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "ID of the department"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Department deleted"
            )
        ]
    )]
    public function destroy($id)
    {
        $department = DB::table('mst_departments')
            ->where('id', $id)
            ->first();

        if (!$department) {
            return response()->json([
                'status' => false,
                'message' => 'Department not found'
            ], 404);
        }

        DB::table('mst_departments')
            ->where('id', $id)
            ->delete();

        return response()->json([
            'status' => true,
            'message' => 'Department deleted successfully'
        ]);
    }
}