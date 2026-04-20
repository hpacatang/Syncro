<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedbackController;

// Your default auth route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ===== FEEDBACK ENDPOINTS (PUBLIC) =====
// Get all feedback for a submission
Route::get('/submissions/{submission_id}/feedback', [FeedbackController::class, 'index']);

// Add feedback to submission
Route::post('/submissions/{submission_id}/feedback', [FeedbackController::class, 'store']);

// Manage individual feedback
Route::get('/feedback/{id}', [FeedbackController::class, 'show']);
Route::put('/feedback/{id}', [FeedbackController::class, 'update']);
Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy']);
