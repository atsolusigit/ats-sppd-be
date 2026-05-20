<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            /**
             * =====================================================
             * TEMP SIMPLE PERMISSION CHECK (BASED ROLE)
             * =====================================================
             * nanti bisa diganti ke table permissions
             */

            $permissions = $this->getPermissionsByRole($user->role_id);

            if (!in_array($permission, $permissions)) {
                return response()->json([
                    'message' => "Unauthorized permission: {$permission}"
                ], 403);
            }

            return $next($request);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token expired'
            ], 401);

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'message' => 'Token invalid'
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token missing or error',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Dummy permission mapping
     * (nanti bisa pindah ke DB table permissions)
     */
    // private function getPermissionsByRole($roleId)
    // {
    //     return match ($roleId) {
    //         1 => ['approve_user', 'change_password', 'view_all'], // admin
    //         2 => ['change_password'],
    //         default => [],
    //     };
    // }
    private function getPermissionsByRole($roleId)
    {
        return cache()->remember("role_permissions_$roleId", 3600, function () use ($roleId) {
            return \DB::table('mst_permissions')
                ->join('role_permissions', 'mst_permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role_id', $roleId)
                ->pluck('mst_permissions.slug')
                ->toArray();
        });
    }
}