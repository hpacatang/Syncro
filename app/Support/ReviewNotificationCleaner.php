<?php

namespace App\Support;

use App\Models\Submission;
use App\Notifications\SubmissionQueuedForReview;
use Illuminate\Notifications\DatabaseNotification;

class ReviewNotificationCleaner
{
    /**
     * Mark queued-for-review notifications as read for a submission once it has been seen or reviewed by staff,
     * removing it from unread notification count/numbers while preserving the content in the notification list.
     */
    public static function clearForSubmission(Submission|int|string|null $submission): void
    {
        $submissionId = $submission instanceof Submission ? $submission->id : (int) $submission;
        if (! $submissionId) {
            return;
        }

        DatabaseNotification::query()
            ->where(function ($query) {
                $query->where('type', SubmissionQueuedForReview::class)
                    ->orWhere('type', 'App\\Notifications\\SubmissionQueuedForReview')
                    ->orWhere('type', 'like', '%SubmissionQueuedForReview%');
            })
            ->whereNull('read_at')
            ->get()
            ->filter(function (DatabaseNotification $notif) use ($submissionId) {
                $subId = $notif->data['submission_id'] ?? null;
                return (int) $subId === (int) $submissionId;
            })
            ->each(function (DatabaseNotification $notif) {
                $notif->markAsRead();
            });
    }

    /**
     * Mark stale queued notifications as read for submissions that have already progressed past 'submitted',
     * clearing the unread count/numbers while preserving notification contents.
     */
    public static function pruneStaleQueuedNotifications(): void
    {
        $activeSubmittedIds = Submission::withoutGlobalScopes()
            ->where('workflow_status', 'submitted')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DatabaseNotification::query()
            ->where(function ($query) {
                $query->where('type', SubmissionQueuedForReview::class)
                    ->orWhere('type', 'App\\Notifications\\SubmissionQueuedForReview')
                    ->orWhere('type', 'like', '%SubmissionQueuedForReview%');
            })
            ->whereNull('read_at')
            ->get()
            ->filter(function (DatabaseNotification $notif) use ($activeSubmittedIds) {
                $subId = $notif->data['submission_id'] ?? null;
                return ! $subId || ! in_array((int) $subId, $activeSubmittedIds, true);
            })
            ->each(function (DatabaseNotification $notif) {
                $notif->markAsRead();
            });
    }
}
