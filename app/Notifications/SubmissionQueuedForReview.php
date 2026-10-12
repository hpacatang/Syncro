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
        $submitter = $this->submission->user?->displayName() ?? 'A user';
        $team = $this->submission->reviewOwnerLabel();

        return [
            'title' => 'New submission to review',
            'message' => "{$submitter} submitted content for {$team} (submission #{$this->submission->id}).",
            'submission_id' => $this->submission->id,
            'type' => 'submission_queued',
            'open_enhance' => true,
            'url' => route('dashboard', ['enhance' => $this->submission->id]),
        ];
    }
}
