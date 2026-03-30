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

// 1. Org/Department Route (Mobile App)
Route::post('/submissions', [SubmissionController::class, 'store']); 

// 2. PAIR Office Routes (Web Dashboard)
Route::get('/submissions/pending', [SubmissionController::class, 'index']); 
Route::post('/submissions/{id}/enhance', [SubmissionController::class, 'enhance']); 
Route::put('/submissions/{id}/approve', [SubmissionController::class, 'approve']); 

// 3. Messaging / Feedback Route (For both PAIR and Org)
Route::post('/submissions/{submission_id}/feedback', [FeedbackController::class, 'store']);