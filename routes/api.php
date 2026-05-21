<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserApprovalController;
use App\Http\Controllers\EmailDomainController;
use App\Http\Controllers\MstRoleController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\ApprovalFlowController;
use App\Http\Controllers\JabatanApprovalController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SppdController;

// ============================
//  Auth Routes (tanpa token)
// ============================

Route::post('/register', [AuthController::class, 'register']); // Registrasi user baru (status = pending)
Route::post('/login', [AuthController::class, 'login']);       // Login dan menerima JWT token
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api'); // Logout user

Route::middleware(['auth:api', 'permission:change_password'])->group(function () {
    Route::put('/change-password', [AuthController::class, 'changePassword']);
});

// Tes protected route (cek token valid)
Route::middleware('auth:api')->get('/protected', function () {return response()->json(['message' => 'You are authenticated']);});

//  Cek user dari token sanctum (tidak dipakai JWT)
Route::get('/user', function (Request $request) {return $request->user();})->middleware('auth:sanctum');

// Cek token yang masih aktif
Route::middleware(['auth:api'])->get('/check-token', [AuthController::class, 'checkToken']);

Route::prefix('admin/users')
    ->middleware(['auth:api', 'permission:user.approve']) // Hanya user dengan permission 'user.approval' yang bisa akses
    ->group(function () {

        Route::get('/pending', [UserApprovalController::class, 'pending']);

        Route::post('/{id}/approve', [UserApprovalController::class, 'approve']);

        Route::post('/{id}/reject', [UserApprovalController::class, 'reject']);
    });

Route::middleware(['auth:api'])->group(function () {

    // LIST
    Route::get('/permissions', [PermissionController::class, 'index'])
        ->middleware('permission:permission.view');

    // DETAIL
    Route::get('/permissions/{id}', [PermissionController::class, 'show'])
        ->middleware('permission:permission.view');

    // CREATE
    Route::post('/permissions', [PermissionController::class, 'store'])
        ->middleware('permission:permission.create');

    // UPDATE
    Route::put('/permissions/{id}', [PermissionController::class, 'update'])
        ->middleware('permission:permission.update');

    // DELETE
    Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])
        ->middleware('permission:permission.delete');
});

Route::middleware(['auth:api'])->group(function () {

    // VIEW LIST
    Route::get('/email-domains', [EmailDomainController::class, 'index'])
        ->middleware('permission:email_domain.view');

    // CREATE
    Route::post('/email-domains', [EmailDomainController::class, 'store'])
        ->middleware('permission:email_domain.create');

    // UPDATE DOMAIN
    Route::put('/email-domains/{id}', [EmailDomainController::class, 'update'])
        ->middleware('permission:email_domain.update');

    // TOGGLE STATUS
    Route::patch('/email-domains/{id}/status', [EmailDomainController::class, 'updateStatus'])
        ->middleware('permission:email_domain.update');

    // DELETE
    Route::delete('/email-domains/{id}', [EmailDomainController::class, 'destroy'])
        ->middleware('permission:email_domain.delete');
});

Route::middleware(['auth:api'])->group(function () {

    // ROLE LIST
    Route::get('/roles', [MstRoleController::class, 'index'])
        ->middleware('permission:role.view');

    // ROLE DETAIL
    Route::get('/roles/{id}', [MstRoleController::class, 'show'])
        ->middleware('permission:role.view');

    // CREATE ROLE
    Route::post('/roles', [MstRoleController::class, 'store'])
        ->middleware('permission:role.create');

    // UPDATE ROLE
    Route::put('/roles/{id}', [MstRoleController::class, 'update'])
        ->middleware('permission:role.update');

    // TOGGLE ROLE STATUS
    Route::patch('/roles/{id}/status', [MstRoleController::class, 'updateStatus'])
        ->middleware('permission:role.update_status');

    // DELETE ROLE
    Route::delete('/roles/{id}', [MstRoleController::class, 'destroy'])
        ->middleware('permission:role.delete');

    // ASSIGN ROLE PERMISSIONS
    Route::post('/roles/{id}/permissions', [MstRoleController::class, 'assignPermissions'])
        ->middleware('permission:role.assign_permission');

    // GET ROLE PERMISSIONS
    Route::get('/roles/{id}/permissions', [MstRoleController::class, 'getPermissions'])
        ->middleware('permission:role.view');
});

Route::middleware(['auth:api'])->group(function () {

    // VIEW LIST
    Route::get('/departments', [DepartmentController::class, 'index'])
        ->middleware('permission:department.view');

    // CREATE
    Route::post('/departments', [DepartmentController::class, 'store'])
        ->middleware('permission:department.create');

    // UPDATE
    Route::put('/departments/{id}', [DepartmentController::class, 'update'])
        ->middleware('permission:department.update');

    // TOGGLE STATUS
    Route::patch('/departments/{id}/status', [DepartmentController::class, 'updateStatus'])
        ->middleware('permission:department.update');

    // DELETE
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])
        ->middleware('permission:department.delete');
});

Route::middleware(['auth:api'])->group(function () {

    // VIEW LIST
    Route::get('/jabatans', [JabatanController::class, 'index'])
        ->middleware('permission:jabatan.view');

    // DETAIL
    Route::get('/jabatans/{id}', [JabatanController::class, 'show'])
        ->middleware('permission:jabatan.view');

    // CREATE
    Route::post('/jabatans', [JabatanController::class, 'store'])
        ->middleware('permission:jabatan.create');

    // UPDATE
    Route::put('/jabatans/{id}', [JabatanController::class, 'update'])
        ->middleware('permission:jabatan.update');

    // TOGGLE STATUS
    Route::patch('/jabatans/{id}/status', [JabatanController::class, 'updateStatus'])
        ->middleware('permission:jabatan.update');

    // DELETE
    Route::delete('/jabatans/{id}', [JabatanController::class, 'destroy'])
        ->middleware('permission:jabatan.delete');
});

Route::middleware(['auth:api'])->group(function () {

    // VIEW LIST
    Route::get('/approval-flows', [ApprovalFlowController::class, 'index'])
        ->middleware('permission:approval_flow.view');

    // DETAIL
    Route::get('/approval-flows/{id}', [ApprovalFlowController::class, 'show'])
        ->middleware('permission:approval_flow.view');

    // CREATE
    Route::post('/approval-flows', [ApprovalFlowController::class, 'store'])
        ->middleware('permission:approval_flow.create');

    // UPDATE
    Route::put('/approval-flows/{id}', [ApprovalFlowController::class, 'update'])
        ->middleware('permission:approval_flow.update');

    // TOGGLE STATUS
    Route::patch('/approval-flows/{id}/status', [ApprovalFlowController::class, 'updateStatus'])
        ->middleware('permission:approval_flow.update');

    // DELETE
    Route::delete('/approval-flows/{id}', [ApprovalFlowController::class, 'destroy'])
        ->middleware('permission:approval_flow.delete');
});

Route::middleware(['auth:api'])->group(function () {

    // VIEW LIST
    Route::get('/jabatan-approvals', [JabatanApprovalController::class, 'index'])
        ->middleware('permission:jabatan_approval.view');

    // DETAIL
    Route::get('/jabatan-approvals/{id}', [JabatanApprovalController::class, 'show'])
        ->middleware('permission:jabatan_approval.view');

    // CREATE
    Route::post('/jabatan-approvals', [JabatanApprovalController::class, 'store'])
        ->middleware('permission:jabatan_approval.create');

    // UPDATE
    Route::put('/jabatan-approvals/{id}', [JabatanApprovalController::class, 'update'])
        ->middleware('permission:jabatan_approval.update');

    // DELETE
    Route::delete('/jabatan-approvals/{id}', [JabatanApprovalController::class, 'destroy'])
        ->middleware('permission:jabatan_approval.delete');
});

Route::middleware(['auth:api'])->group(function () {

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:user.view');
});


Route::middleware(['auth:api'])->group(function () {

    Route::get(
        '/sppd',[SppdController::class, 'index']
    )->middleware('permission:sppd.view');

    Route::get('/sppd/{id}',[SppdController::class, 'show']
    )->middleware('permission:sppd.view');

    Route::post('/sppd',[SppdController::class, 'store']
    )->middleware('permission:sppd.create');

    Route::put('/sppd/{id}',[SppdController::class, 'update']
    )->middleware('permission:sppd.update');

    Route::delete('/sppd/{id}',
        [SppdController::class, 'destroy']
    )->middleware('permission:sppd.delete');

});