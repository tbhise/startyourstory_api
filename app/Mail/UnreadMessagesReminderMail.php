<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Periodic unread-messages reminder (max one per user per 3 hours — throttled
 * by SendUnreadMessagesEmailJob via user_message_email_state). Replaces the
 * old per-message NewMessageReplyMail flood: one summary email instead of one
 * email per message. Push remains the realtime channel.
 */
class UnreadMessagesReminderMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly object $recipient,
        public readonly int $unreadCount,
        public readonly int $conversationCount
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        // Same sender identity as the per-message mail it replaces.
        return EmailPurpose::MESSAGE_REPLY;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You have {$this->unreadCount} unread " .
                ($this->unreadCount === 1 ? 'message' : 'messages') . " — StartYourStory",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.messaging.unread-reminder',
            with: [
                'recipientName'     => $this->recipient->name,
                'unreadCount'       => $this->unreadCount,
                'conversationCount' => $this->conversationCount,
                'appName'           => 'StartYourStory',
                'messagesUrl'       => config('app.frontend_url', 'https://startyourstory.in') . '/messages',
            ],
        );
    }
}
