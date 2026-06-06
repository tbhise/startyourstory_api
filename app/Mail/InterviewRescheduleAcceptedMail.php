<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewRescheduleAcceptedMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string  $candidateName,
        public readonly string  $firmName,
        public readonly string  $jobTitle,
        public readonly string  $interviewDate,
        public readonly ?string $interviewNote
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::INTERVIEW_RESCHEDULE_ACCEPTED;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Interview Reschedule Accepted — {$this->firmName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview.reschedule-accepted',
            with: [
                'candidateName' => $this->candidateName,
                'firmName'      => $this->firmName,
                'jobTitle'      => $this->jobTitle,
                'interviewDate' => $this->interviewDate,
                'interviewNote' => $this->interviewNote,
            ],
        );
    }
}
