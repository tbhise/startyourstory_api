<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewInviteResponseMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $firmName,
        public readonly string $candidateName,
        public readonly bool   $accepted,
        public readonly string $viewUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::INTERVIEW_INVITE_RESPONSE;
    }

    public function envelope(): Envelope
    {
        $verb = $this->accepted ? 'accepted' : 'declined';
        return new Envelope(
            subject: "{$this->candidateName} {$verb} your interview invitation — Start Your Story",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview.invite-response',
            with: [
                'firmName'      => $this->firmName,
                'candidateName' => $this->candidateName,
                'accepted'      => $this->accepted,
                'viewUrl'       => $this->viewUrl,
            ],
        );
    }
}
