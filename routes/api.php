<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\UserManagementController;

// Your default auth route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ===== AUDIT LOGS ENDPOINTS (ADMIN/PAIR ONLY) =====
Route::middleware('auth:sanctum', 'api.role:admin,pair')->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
});

// ===== USER MANAGEMENT ENDPOINTS (ADMIN/PAIR ONLY) =====
// Use distinct route names so they do not override web Route::resource('users', …) names.
Route::middleware('auth:sanctum', 'api.role:admin,pair')->group(function () {
    Route::apiResource('users', UserManagementController::class)->names([
        'index' => 'api.users.index',
        'store' => 'api.users.store',
        'show' => 'api.users.show',
        'update' => 'api.users.update',
        'destroy' => 'api.users.destroy',
    ]);
});

// ===== FEEDBACK ENDPOINTS (PUBLIC) =====
// Get all feedback for a submission
Route::get('/submissions/{submission_id}/feedback', [FeedbackController::class, 'index']);

// Add feedback to submission
Route::post('/submissions/{submission_id}/feedback', [FeedbackController::class, 'store']);

// Manage individual feedback
Route::get('/feedback/{id}', [FeedbackController::class, 'show']);
Route::put('/feedback/{id}', [FeedbackController::class, 'update']);
Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy']);
