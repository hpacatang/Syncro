<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionUnderReview extends Notification
{
    use Queueable;

    public function __construct(public readonly Submission $submission) {}

    /**
     * Deliver via database (in-app bell) and optionally mail.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * E-mail representation.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $id  = $this->submission->id;
        $url = route('org.submissions.review', $this->submission);

        $greeting = $notifiable->name
            ? 'Hello ' . $notifiable->name . ','
            : 'Hello,';

        return (new MailMessage)
            ->subject('Your Submission Is Now Under PAIR Review')
            ->greeting($greeting)
            ->line("Great news! Submission #{$id} has been picked up by the PAIR office and is currently under review.")
            ->line('Our team will either approve or send feedback shortly. You will be notified once a decision is made.')
            ->action('View Submission', $url)
            ->line('Thank you for submitting to our platform!');
    }

    /**
     * In-app (database) representation shown in the notification bell.
     */
    public function toArray(object $notifiable): array
    {
        $id = $this->submission->id;

        return [
            'title'         => 'Submission under PAIR review',
            'message'       => "Your submission #{$id} is now being reviewed by the PAIR office.",
            'submission_id' => $id,
            'type'          => 'submission_under_review',
            'url'           => route('org.submissions.review', $this->submission),
        ];
    }
}
