<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MainController;

Route::get('/', function () {
    return redirect('login');
});

Route::get('/login', [UserController::class, 'login'])->name('login');
Route::post('authenticate', [UserController::class, 'authenticate'])->name('authenticate');
Route::get('/register', [UserController::class, 'register'])->name('register');
Route::post('store', [UserController::class, 'store'])->name('store');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function(){
    // Admin & PAIR Dashboard
    Route::middleware('role:admin,pair')->group(function(){
        Route::get('/dashboard', [MainController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/submissions', [MainController::class, 'submissions'])->name('dashboard.submissions');
        Route::get('/dashboard/notifications', [MainController::class, 'notifications'])->name('dashboard.notifications');
    });

    // Organization Dashboard
    Route::middleware('role:org')->group(function(){
        Route::get('/org/dashboard', [MainController::class, 'orgDashboard'])->name('org.dashboard');
        Route::get('/org/submit', function() {
            return view('Submission.OrgSubmit');
        })->name('org.submit');
        Route::get('/org/submissions', [MainController::class, 'submissions'])->name('org.submissions');
        Route::get('/org/notifications', [MainController::class, 'notifications'])->name('org.notifications');
    });

    // API Routes for Submissions
    Route::prefix('api')->group(function(){
        // Submission endpoints
        Route::post('/submissions', [\App\Http\Controllers\SubmissionController::class, 'store']); 
        Route::get('/submissions', [\App\Http\Controllers\SubmissionController::class, 'index']);
        Route::get('/submissions/{id}', [\App\Http\Controllers\SubmissionController::class, 'show']);
        Route::post('/submissions/{id}/enhance', [\App\Http\Controllers\SubmissionController::class, 'enhance']); 
        Route::post('/submissions/{id}/save-manual-caption', [\App\Http\Controllers\SubmissionController::class, 'saveManualCaption']); 
        Route::put('/submissions/{id}/approve', [\App\Http\Controllers\SubmissionController::class, 'approve']); 
        Route::post('/submissions/{id}/org-review/approve', [\App\Http\Controllers\SubmissionController::class, 'orgApproveEnhancement']);
        Route::post('/submissions/{id}/org-review/reject', [\App\Http\Controllers\SubmissionController::class, 'orgRejectEnhancement']);
        Route::put('/submissions/{id}', [\App\Http\Controllers\SubmissionController::class, 'update']);
        Route::delete('/submissions/{id}', [\App\Http\Controllers\SubmissionController::class, 'destroy']);
        Route::get('/submissions/pending', [\App\Http\Controllers\SubmissionController::class, 'index']);
    });
});