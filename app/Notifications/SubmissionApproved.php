<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionApproved extends Notification
{
    use Queueable;

    public $submission;

    
    public function __construct(Submission $submission)
    {
        $this->submission = $submission;
    }

    
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    
    public function toMail(object $notifiable): MailMessage
    {
        $id = $this->submission->id;
        $reviewUrl = route('org.submissions.review', $this->submission);
        $greeting = $notifiable->name
            ? 'Hello ' . $notifiable->name . ','
            : 'Hello,';

        return (new MailMessage)
            ->subject('Your Post Has Been Approved!')
            ->greeting($greeting)
            ->line('Great news! Your recent submission has been reviewed and approved by the PAIR office.')
            ->line("Submission #{$id} is approved and ready to post.")
            ->action('View Submission', $reviewUrl)
            ->line('Thank you for contributing to our platform!');
    }

    
    public function toArray(object $notifiable): array
    {
        $id = $this->submission->id;

        return [
            'title' => 'Submission approved',
            'submission_id' => $id,
            'type' => 'submission_approved',
            'message' => 'Your submission has been approved and is ready to post.',
            'url' => route('org.submissions.review', $this->submission),
        ];
    }
}
