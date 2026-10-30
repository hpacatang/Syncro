<?php

namespace App\Notifications;

use App\Models\Submission;
use App\Notifications\Concerns\EnrichesNotificationPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionPublished extends Notification
{
    use Queueable, EnrichesNotificationPayload;

    public function __construct(public Submission $submission)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $id = $this->submission->id;
        $reviewUrl = route('org.submissions.review', $this->submission);
        $greeting = $notifiable->name ? 'Hello ' . $notifiable->name . ',' : 'Hello,';

        return (new MailMessage)
            ->subject('Your Post Has Been Published!')
            ->greeting($greeting)
            ->line("Submission #{$id} has been published to social media.")
            ->action('View Published Submission', $reviewUrl)
            ->line('Thank you for sharing your content with the community!');
    }

    public function toArray(object $notifiable): array
    {
        return $this->enrichPayload([
            'title' => 'Submission published',
            'submission_id' => $this->submission->id,
            'type' => 'submission_published',
            'message' => 'Your post has been published to social media.',
            'url' => route('org.submissions.review', $this->submission),
        ]);
    }
}
