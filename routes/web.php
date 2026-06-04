<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\SubmissionReviewController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StaffToolsController;
use App\Http\Controllers\SubmissionLifecycleController;

Route::get('/', function () {
    return redirect('login');
});

Route::get('/login', [UserController::class, 'login'])->name('login');
Route::get('authenticate', fn () => redirect()->route('login'));
Route::post('authenticate', [UserController::class, 'authenticate'])->name('authenticate');
Route::get('/register', [UserController::class, 'register'])->name('register');
Route::get('store', fn () => redirect()->route('register'));
Route::post('store', [UserController::class, 'store'])->name('store');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    Route::middleware('role:super_admin,admin,pair')->group(function () {
        Route::get('/dashboard', [MainController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/submissions', [MainController::class, 'submissions'])->name('dashboard.submissions');
        Route::get('/dashboard/submissions/{submission}/review', [SubmissionReviewController::class, 'show'])
            ->name('dashboard.submissions.review');
        Route::get('/dashboard/notifications', [NotificationController::class, 'index'])->name('dashboard.notifications');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

        Route::resource('users', UserManagementController::class);
    });

    Route::middleware('role:org,department')->group(function () {
        Route::get('/org/dashboard', [MainController::class, 'orgDashboard'])->name('org.dashboard');
        Route::get('/org/submit', function () {
            return view('Submission.OrgSubmit');
        })->name('org.submit');
        Route::get('/org/submissions', [MainController::class, 'submissions'])->name('org.submissions');
        Route::get('/org/submissions/{submission}/review', [SubmissionReviewController::class, 'show'])
            ->name('org.submissions.review');
        Route::get('/org/notifications', [NotificationController::class, 'index'])->name('org.notifications');
    });

    Route::middleware('isAdmin')->group(function () {
        Route::get('/dashboard/media-gallery', [StaffToolsController::class, 'mediaGallery'])->name('staff.media-gallery');
        Route::get('/dashboard/caption-assist', [StaffToolsController::class, 'captionAssist'])->name('staff.caption-assist');
        Route::post('/api/pair/caption-from-media', [StaffToolsController::class, 'captionFromMedia'])->name('api.pair.caption-from-media');
    });

    Route::prefix('api')->group(function () {
        Route::middleware('role:org,department')->group(function () {
            Route::post('/submissions', [SubmissionController::class, 'store']);
            Route::post('/submissions/{id}/org-review/approve', [SubmissionController::class, 'orgApproveEnhancement']);
            Route::post('/submissions/{id}/org-review/reject', [SubmissionController::class, 'orgRejectEnhancement']);
        });

        Route::middleware('role:super_admin,admin,pair')->group(function () {
            Route::post('/submissions/{id}/enhance', [SubmissionController::class, 'enhance']);
            Route::post('/submissions/{id}/save-manual-caption', [SubmissionController::class, 'saveManualCaption']);
            Route::put('/submissions/{id}/approve', [SubmissionController::class, 'approve']);
            Route::post('/submissions/{submission}/transition', [SubmissionLifecycleController::class, 'transition']);
        });

        Route::get('/submissions', [SubmissionController::class, 'index']);
        Route::get('/submissions/pending', [SubmissionController::class, 'index']);
        Route::get('/submissions/lifecycle-updates', [SubmissionLifecycleController::class, 'updates']);
        Route::get('/submissions/{submission}/lifecycle', [SubmissionLifecycleController::class, 'show']);
        Route::get('/submissions/{id}', [SubmissionController::class, 'show']);
        Route::put('/submissions/{id}', [SubmissionController::class, 'update']);
        Route::delete('/submissions/{id}', [SubmissionController::class, 'destroy']);
    });
});
