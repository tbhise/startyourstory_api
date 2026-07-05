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
 * Student re-engagement + feature release campaign.
 *
 * First user of the premium layout (emails/layouts/premium.blade.php) — the
 * reusable branded header/footer system. Preview at /dev/emails (local).
 */
class StudentFeatureReleaseMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string  $name = 'Candidate',
        public readonly ?string $ctaUrl = null,
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::REENGAGEMENT;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Big updates are now live on StartYourStory 🚀',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign.student-feature-release',
            with: [
                'name'   => $this->name,
                'ctaUrl' => $this->ctaUrl
                    ?? rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/') . '/login',
            ],
        );
    }
}
