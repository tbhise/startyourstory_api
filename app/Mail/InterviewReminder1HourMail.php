<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewReminder1HourMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string  $candidateName,
        public readonly string  $firmName,
        public readonly string  $jobTitle,
        public readonly string  $interviewDate,
        public readonly string  $interviewMode,
        public readonly ?string $interviewNote,
        public readonly string  $viewDetailsUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::INTERVIEW_REMINDER_1H;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: Your Interview Starts in 1 Hour — {$this->firmName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview.reminder',
            with: [
                'candidateName' => $this->candidateName,
                'firmName'      => $this->firmName,
                'jobTitle'      => $this->jobTitle,
                'interviewDate' => $this->interviewDate,
                'interviewMode' => $this->interviewMode,
                'interviewNote' => $this->interviewNote,
                'viewDetailsUrl' => $this->viewDetailsUrl,
                'hoursAway'     => 1,
            ],
        );
    }
}
