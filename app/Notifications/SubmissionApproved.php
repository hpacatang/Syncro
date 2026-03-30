<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionApproved extends Notification
{
    use Queueable;

    public $submission;

    /**
     * Create a new notification instance.
     */
    public function __construct($submission)
    {
        $this->submission = $submission;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Post Has Been Approved!')
            ->line('Great news! Your recent submission has been reviewed and approved by the PAIR office.')
            ->action('View Submission', url('/submissions/' . $this->submission->id))
            ->line('Thank you for contributing to our platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'message' => 'Your submission has been approved and is ready to post.'
        ];
    }
}
