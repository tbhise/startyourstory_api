<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewRejectedMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $candidateName,
        public readonly string $jobTitle,
        public readonly string $viewApplicationsUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::INTERVIEW_REJECTED;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->candidateName} Declined Your Interview Request — Start Your Story",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview.rejected',
            with: [
                'candidateName'       => $this->candidateName,
                'jobTitle'            => $this->jobTitle,
                'viewApplicationsUrl' => $this->viewApplicationsUrl,
            ],
        );
    }
}
