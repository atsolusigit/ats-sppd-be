<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class EmailDomainController extends Controller
{

    #[OA\Get(
        path: "/api/email-domains",
        tags: ["Email Domains"],
        summary: "Get all email domains",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "domain", type: "string"),
                                    new OA\Property(property: "status", type: "boolean"),
                                    new OA\Property(property: "created_by", type: "integer", nullable: true),
                                    new OA\Property(property: "updated_by", type: "integer", nullable: true),
                                    new OA\Property(property: "created_at", type: "string"),
                                    new OA\Property(property: "updated_at", type: "string"),
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
        return DB::table('mst_email_domains')->get();
    }

    #[OA\Post(
        path: "/api/email-domains",
        tags: ["Email Domains"],
        summary: "Create email domain",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["domain"],
                properties: [
                    new OA\Property(property: "domain", type: "string", example: "gmail.com"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Created",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Created")
                    ]
                )
            )
        ]
    )]


    // STORE
    public function store(Request $request)
    {
        $request->validate([
            'domain' => 'required|string|unique:mst_email_domains,domain'
        ]);

        DB::table('mst_email_domains')->insert([
            'domain' => $request->domain,
            'status' => 1,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Created']);
    }

    #[OA\Patch(
        path: "/api/email-domains/{id}/status",
        tags: ["Email Domains"],
        summary: "Toggle status email domain",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID of the email domain"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Status updated",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Updated")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Not found"
            )
        ]
    )]

    // UPDATE STATUS
    public function updateStatus($id)
    {
        $domain = DB::table('mst_email_domains')->where('id', $id)->first();

        if (!$domain) {
            return response()->json(['message' => 'Not found'], 404);
        }

        DB::table('mst_email_domains')
            ->where('id', $id)
            ->update([
                'status' => !$domain->status,
                'updated_by' => auth()->id(),
                'updated_at' => now()
            ]);

        return response()->json(['message' => 'Updated']);
    }
    
    #[OA\Delete(
        path: "/api/email-domains/{id}",
        tags: ["Email Domains"],
        summary: "Delete email domain",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID of the email domain"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Deleted",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Deleted")
                    ]
                )
            )
        ]
    )]

    // DELETE
    public function destroy($id)
    {
        DB::table('mst_email_domains')->where('id', $id)->delete();

        return response()->json(['message' => 'Deleted']);
    }

    #[OA\Put(
        path: "/api/email-domains/{id}",
        tags: ["Email Domains"],
        summary: "Update email domain",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID of the email domain"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["domain"],
                properties: [
                    new OA\Property(property: "domain", type: "string", example: "yahoo.com"),
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'domain' => 'required|string|unique:mst_email_domains,domain,' . $id
        ]);

        $domain = DB::table('mst_email_domains')->where('id', $id)->first();

        if (!$domain) {
            return response()->json([
                'status' => false,
                'message' => 'Data not found'
            ], 404);
        }

        DB::table('mst_email_domains')
            ->where('id', $id)
            ->update([
                'domain' => $request->domain,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Domain updated successfully'
        ]);
    }
}