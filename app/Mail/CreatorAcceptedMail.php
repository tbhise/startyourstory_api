<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreatorAcceptedMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $firmName,
        public readonly string $creatorName,
        public readonly string $projectTitle,
        public readonly string $contractUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::CREATOR_ACCEPTED;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Creator accepted your project — Start Your Story",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.creator.accepted',
            with: [
                'firmName'     => $this->firmName,
                'creatorName'  => $this->creatorName,
                'projectTitle' => $this->projectTitle,
                'contractUrl'  => $this->contractUrl,
            ],
        );
    }
}
