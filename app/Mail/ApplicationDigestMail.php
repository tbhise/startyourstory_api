<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationDigestMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{candidate_name: string, job_title: string, applied_at: string}>  $applications
     */
    public function __construct(
        public readonly string $firmName,
        public readonly array  $applications,
        public readonly string $viewApplicationsUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::APPLICATION_DIGEST;
    }

    public function envelope(): Envelope
    {
        $count = count($this->applications);
        $noun  = $count === 1 ? 'New Application' : 'New Applications';

        return new Envelope(
            subject: "{$count} {$noun} Received — Start Your Story",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application.digest',
            with: [
                'firmName'            => $this->firmName,
                'applications'        => $this->applications,
                'applicationCount'    => count($this->applications),
                'viewApplicationsUrl' => $this->viewApplicationsUrl,
            ],
        );
    }
}
