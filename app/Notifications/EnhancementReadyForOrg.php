<?php

namespace App\Notifications;

use App\Models\Submission;
use App\Notifications\Concerns\EnrichesNotificationPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnhancementReadyForOrg extends Notification
{
    use Queueable, EnrichesNotificationPayload;

    public function __construct(public Submission $submission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $enhancer = auth()->user() ?? $this->submission->enhancer;

        return $this->enrichPayload([
            'title' => 'Caption ready for your review',
            'message' => 'The review team updated your submission. Please review and approve or request changes.',
            'submission_id' => $this->submission->id,
            'url' => route('org.submissions.review', $this->submission),
        ], $enhancer);
    }
}
