<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly object $recipient,
        public readonly ?object $firmProfile,
        public readonly string $messagePreview,
        public readonly ?object $candidateUser = null
    ) {}

    public function envelope(): Envelope
    {
        $senderName = $this->firmProfile
            ? $this->firmProfile->firm_name
            : ($this->candidateUser->name ?? 'Someone');

        return new Envelope(
            subject: "New Message Request from {$senderName} — StartYourStory",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.messaging.new-request',
            with: [
                'recipientName'   => $this->recipient->name,
                'senderName'      => $this->firmProfile
                    ? $this->firmProfile->firm_name
                    : ($this->candidateUser->name ?? 'Someone'),
                'messagePreview'  => mb_substr($this->messagePreview, 0, 200),
                'appName'         => 'StartYourStory',
                'messagesUrl'     => config('app.frontend_url', 'https://startyourstory.in') . '/messages',
            ],
        );
    }
}
