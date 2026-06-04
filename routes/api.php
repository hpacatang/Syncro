<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'api.role:admin,pair'])->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    Route::apiResource('users', UserManagementController::class)->names([
        'index' => 'api.users.index',
        'store' => 'api.users.store',
        'show' => 'api.users.show',
        'update' => 'api.users.update',
        'destroy' => 'api.users.destroy',
    ]);
});

Route::get('/submissions/{submission_id}/feedback', [FeedbackController::class, 'index']);
Route::post('/submissions/{submission_id}/feedback', [FeedbackController::class, 'store']);
Route::get('/feedback/{id}', [FeedbackController::class, 'show']);
Route::put('/feedback/{id}', [FeedbackController::class, 'update']);
Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy']);
