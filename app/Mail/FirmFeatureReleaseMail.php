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
 * Firm re-engagement + feature release campaign — firm counterpart of
 * StudentFeatureReleaseMail (same premium layout). Preview at /dev/emails.
 */
class FirmFeatureReleaseMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string  $name = 'Hiring Partner',
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
            view: 'emails.campaign.firm-reengagement-feature',
            with: [
                'name'   => $this->name,
                'ctaUrl' => $this->ctaUrl
                    ?? rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/') . '/login',
            ],
        );
    }
}
