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
 * Daily reminder to a firm that one or more of its active job posts have
 * applicants still awaiting review. Sent by SendFirmApplicantReminderJob.
 *
 * @param array<int,array{title:string,count:int}> $jobs
 */
class FirmApplicantReminderMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $firmName,
        public readonly array  $jobs,
        public readonly int    $totalCount,
        public readonly string $viewUrl,
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::FIRM_APPLICANT_REMINDER;
    }

    public function envelope(): Envelope
    {
        $noun = $this->totalCount === 1 ? 'applicant is' : 'applicants are';

        return new Envelope(
            subject: "{$this->totalCount} {$noun} waiting for your review — Start Your Story",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.firm.applicant-reminder',
            with: [
                'firmName'   => $this->firmName,
                'jobs'       => $this->jobs,
                'totalCount' => $this->totalCount,
                'viewUrl'    => $this->viewUrl,
            ],
        );
    }
}
