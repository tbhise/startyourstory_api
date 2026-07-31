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
 * Technology Solutions vertical launch announcement (premium layout).
 * Preview at /dev/emails.
 */
class TechnologySolutionsLaunchMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string  $name = 'there',
        public readonly ?string $ctaUrl = null,
        public readonly ?string $openPixelUrl = null,
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::MARKETING;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚀 Introducing Technology Solutions by Start Your Story',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign.technology-solutions-launch',
            with: [
                'name'   => $this->name,
                'ctaUrl' => $this->ctaUrl
                    ?? rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/') . '/technology-solutions#contact',
                // Only campaign sends pass this; the layout skips the pixel when null.
                'openPixelUrl' => $this->openPixelUrl,
            ],
        );
    }
}
