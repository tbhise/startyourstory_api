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
 * Reminder to a student who has an interview invitation still awaiting their
 * accept/reject. Sent at escalating stages (24h / 72h / 5 days) by
 * SendInterviewResponseReminderJob. $isFinal flags the last (5-day) reminder.
 */
class InterviewResponseReminderMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $candidateName,
        public readonly string $firmName,
        public readonly string $respondUrl,
        public readonly bool   $isFinal = false,
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::INTERVIEW_RESPONSE_REMINDER;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Interview Response Pending');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview.response-reminder',
            with: [
                'candidateName' => $this->candidateName,
                'firmName'      => $this->firmName,
                'respondUrl'    => $this->respondUrl,
                'isFinal'       => $this->isFinal,
            ],
        );
    }
}
