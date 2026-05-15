<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionQueuedForReview extends Notification
{
    use Queueable;

    public function __construct(public Submission $submission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $org = $this->submission->user?->name ?? 'An organization';

        return [
            'title' => 'New submission to review',
            'message' => "{$org} submitted content for PAIR (submission #{$this->submission->id}).",
            'submission_id' => $this->submission->id,
            'url' => route('dashboard', ['status' => 'pending']),
        ];
    }
}
