<?php

namespace App\Http\Controllers;

use App\Support\NotificationTargetUrl;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

use App\Support\ReviewNotificationCleaner;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->user()->isStaffReviewer()) {
            ReviewNotificationCleaner::pruneStaleQueuedNotifications();
        }

        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('main.notifications', [
            'notifications' => $notifications,
        ]);
    }

    public function unreadCount(Request $request)
    {
        if ($request->user()->isStaffReviewer()) {
            ReviewNotificationCleaner::pruneStaleQueuedNotifications();
        }

        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function recent(Request $request)
    {
        if ($request->user()->isStaffReviewer()) {
            ReviewNotificationCleaner::pruneStaleQueuedNotifications();
        }

        $items = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function (DatabaseNotification $n) use ($request) {
                $data = $n->data;

                $fromOrg = $data['sender_org'] ?? null;
                $fromName = $data['sender_name'] ?? $data['from'] ?? null;
                $submittedAt = $data['submitted_at'] ?? null;
                $scheduledPostAt = $data['scheduled_post_at'] ?? null;
                $message = $data['message'] ?? '';

                // Fallback resolution for legacy notifications
                $submissionId = $data['submission_id'] ?? null;
                if ($submissionId && (! $fromName || $message === 'A new submission was added to the review queue.')) {
                    $submission = \App\Models\Submission::withoutGlobalScopes()->with(['user.department'])->find($submissionId);
                    if ($submission) {
                        $legacyOrg = $submission->user?->displayName();
                        if ($submission->user?->department) {
                            $legacyOrg .= ' (' . $submission->user->department->displayName() . ')';
                        }
                        $fromOrg ??= $legacyOrg;
                        $fromName ??= $legacyOrg;
                        $submittedAt ??= $submission->created_at?->format('M d, Y h:i A');
                        $scheduledPostAt ??= $submission->scheduled_post_at?->format('M d, Y h:i A');
                        if ($message === 'A new submission was added to the review queue.' && $legacyOrg) {
                            $message = "Submitted by {$legacyOrg} on {$submittedAt} for review.";
                        }
                    }
                }

                return [
                    'id' => $n->id,
                    'read' => $n->read_at !== null,
                    'created_at' => $n->created_at?->toIso8601String(),
                    'created_at_formatted' => $n->created_at?->format('M d, Y h:i A'),
                    'title' => $data['title'] ?? class_basename($n->type),
                    'from' => $fromOrg ?? $fromName,
                    'from_org' => $fromOrg,
                    'submitted_at' => $submittedAt,
                    'scheduled_post_at' => $scheduledPostAt,
                    'message' => $message,
                    'url' => NotificationTargetUrl::resolve($data, $request->user()),
                ];
            });

        return response()->json(['notifications' => $items]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }
}
