<?php

namespace App\Notifications;

use App\Models\Submission;
use App\Notifications\Concerns\EnrichesNotificationPayload;
use App\Support\AppTime;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionQueuedForReview extends Notification
{
    use Queueable, EnrichesNotificationPayload;

    public function __construct(public Submission $submission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $submitter = $this->submission->user;
        $submitterName = $submitter?->displayName() ?? 'Unknown User';
        
        $orgName = $submitter?->displayName() ?? 'Unknown Organization';
        if ($submitter?->department) {
            $orgName .= ' (' . $submitter->department->displayName() . ')';
        }

        $team = $this->submission->reviewOwnerLabel();
        $submittedAt = AppTime::format($this->submission->created_at ?? now(), 'M d, Y h:i A');
        $scheduledPost = AppTime::format($this->submission->scheduled_post_at, 'M d, Y h:i A');

        $message = "Submitted by {$orgName} on {$submittedAt} for {$team} review.";
        if ($scheduledPost) {
            $message .= " Target post date: {$scheduledPost}.";
        }

        return $this->enrichPayload([
            'title' => 'New submission to review',
            'message' => $message,
            'submission_id' => $this->submission->id,
            'sender_org' => $orgName,
            'sender_name' => $submitterName,
            'submitted_at' => $submittedAt,
            'scheduled_post_at' => $scheduledPost,
            'type' => 'submission_queued',
            'open_enhance' => true,
            'url' => route('dashboard.submissions.review', $this->submission),
        ], $submitter);
    }
}
