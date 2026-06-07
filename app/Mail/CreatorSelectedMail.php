<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreatorSelectedMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $creatorName,
        public readonly string $projectTitle,
        public readonly string $respondUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::CREATOR_SELECTED;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been selected for a project — Start Your Story",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.creator.selected',
            with: [
                'creatorName'  => $this->creatorName,
                'projectTitle' => $this->projectTitle,
                'respondUrl'   => $this->respondUrl,
            ],
        );
    }
}
