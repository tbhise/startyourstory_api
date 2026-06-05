<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageReplyMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly object $recipient,
        public readonly string $senderName,
        public readonly string $messagePreview
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::MESSAGE_REPLY;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Message from {$this->senderName} — StartYourStory",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.messaging.new-reply',
            with: [
                'recipientName'  => $this->recipient->name,
                'senderName'     => $this->senderName,
                'messagePreview' => mb_substr($this->messagePreview, 0, 200),
                'appName'        => 'StartYourStory',
                'messagesUrl'    => config('app.frontend_url', 'https://startyourstory.in') . '/messages',
            ],
        );
    }
}
