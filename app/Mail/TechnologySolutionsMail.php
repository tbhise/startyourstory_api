<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use App\Services\Marketing\MarketingTemplateRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Marketing outreach to CA firms that are NOT registered on Start Your Story
 * (audience: `marketing_contacts`). Queued by MarketingMailer under the
 * 'technology-solutions' key; preview at /dev/emails.
 *
 * All copy lives in the Blade view so wording/design can be revised without
 * touching the mailer or the command. The subject comes from
 * MarketingTemplateRegistry so there is one source of truth for it.
 *
 * NOT the same mail as TechnologySolutionsLaunchMail, which is the admin
 * Campaign module's announcement to firms already on the platform.
 */
class TechnologySolutionsMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    private const TEMPLATE_KEY = MarketingTemplateRegistry::TECHNOLOGY_SOLUTIONS;

    /**
     * Internal copy of every send of THIS template (transport-level only — the
     * address is never rendered in the email body; the footer shows
     * info@startyourstory.in). Applies to bulk, test and preview sends alike,
     * because it lives on the Mailable rather than in the sending code.
     *
     * NOTE: a CC address is visible to the recipient in their mail client.
     * Switch this to Envelope(bcc: …) if the copy must be invisible.
     */
    private const INTERNAL_CC = 'contact@startyourstory.in';

    /**
     * @param string  $firmName     Contact's firm name (falls back to a neutral greeting).
     * @param ?string $subjectLine  Overrides the registry subject (used for [TEST] sends).
     * @param ?string $ctaUrl       Signed click-tracking URL; defaults to the untracked target.
     * @param ?string $openPixelUrl Signed open-tracking pixel; null = no pixel.
     */
    public function __construct(
        public readonly string  $firmName = 'there',
        public readonly ?string $subjectLine = null,
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
            cc: [self::INTERNAL_CC],
            subject: $this->subjectLine ?: MarketingTemplateRegistry::subjectFor(self::TEMPLATE_KEY),
        );
    }

    public function content(): Content
    {
        $front = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/');

        return new Content(
            view: 'emails.marketing.technology-solutions',
            with: [
                'firmName' => $this->firmName,
                'ctaUrl'   => $this->ctaUrl ?: MarketingTemplateRegistry::ctaUrlFor(self::TEMPLATE_KEY),
                // Secondary CTA. Untracked on purpose: the signed click route
                // redirects to the template's single cta_path, so a second
                // tracked link would land on the wrong page.
                'registerUrl' => $front . '/register?type=firm',
                // Only real sends pass this; the layout skips the pixel when null.
                'openPixelUrl' => $this->openPixelUrl,
            ],
        );
    }
}
