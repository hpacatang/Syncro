<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\FeedbackController;

// Your default auth route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- SYNCRO: SUBMISSION & FEEDBACK ROUTES ---

// ===== SUBMISSION ENDPOINTS =====
// For Org/Department (Mobile App & Web)
Route::post('/submissions', [SubmissionController::class, 'store']); 

// For PAIR & Dashboard - View all submissions with filters
Route::get('/submissions', [SubmissionController::class, 'index']);
Route::get('/submissions/{id}', [SubmissionController::class, 'show']);

// For PAIR - Submission management
Route::post('/submissions/{id}/enhance', [SubmissionController::class, 'enhance']); 
Route::post('/submissions/{id}/save-manual-caption', [SubmissionController::class, 'saveManualCaption']); 
Route::put('/submissions/{id}/approve', [SubmissionController::class, 'approve']); 
Route::put('/submissions/{id}', [SubmissionController::class, 'update']);
Route::delete('/submissions/{id}', [SubmissionController::class, 'destroy']);

// Pending submissions (backward compatibility)
Route::get('/submissions/pending', [SubmissionController::class, 'index']);

// ===== FEEDBACK ENDPOINTS =====
// Get all feedback for a submission
Route::get('/submissions/{submission_id}/feedback', [FeedbackController::class, 'index']);

// Add feedback to submission
Route::post('/submissions/{submission_id}/feedback', [FeedbackController::class, 'store']);

// Manage individual feedback
Route::get('/feedback/{id}', [FeedbackController::class, 'show']);
Route::put('/feedback/{id}', [FeedbackController::class, 'update']);
Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy']);
