<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/**
 * Sent to a student/firm when their support ticket is closed by an admin.
 * Carries the ticket id, category and resolution note (the only email in the
 * support-ticket flow — all other updates are in-app notifications).
 */
class SupportTicketClosedMail extends Mailable implements HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string $userName,
        public string $ticketNo,
        public string $ticketCategory,
        public string $resolutionNote
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::SUPPORT_TICKET_CLOSED;
    }

    public function build()
    {
        return $this->subject('Your Support Ticket ' . $this->ticketNo . ' Has Been Resolved — Start Your Story')
            ->view('emails.support-ticket-closed');
    }
}
