<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewInviteMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string  $candidateName,
        public readonly string  $firmName,
        public readonly ?string $inviteMessage,
        public readonly string  $respondUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::INTERVIEW_INVITE;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->firmName} invited you for an interview — Start Your Story",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview.invite',
            with: [
                'candidateName' => $this->candidateName,
                'firmName'      => $this->firmName,
                'inviteMessage' => $this->inviteMessage,
                'respondUrl'    => $this->respondUrl,
            ],
        );
    }
}
