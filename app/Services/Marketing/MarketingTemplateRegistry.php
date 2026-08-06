<?php

namespace App\Services\Marketing;

use App\Enums\EmailPurpose;
use App\Mail\TechnologySolutionsMail;
use Illuminate\Mail\Mailable;
use InvalidArgumentException;

/**
 * Marketing email templates sendable to the `marketing_contacts` audience.
 *
 * Single source of truth for "which marketing emails exist": the CLI key, the
 * subject, the sender purpose, the `email_logs.template_name` value and where the
 * tracked CTA click lands. MarketingMailer resolves everything through here, so
 * `marketing:send` never names a Mail class and never carries copy.
 *
 * Adding a future template (Academy Launch, Premium Plans, Resume Builder,
 * Feature Announcement, …) = one Mailable + one Blade view + one entry here plus
 * its arm in make(). The command, the mailer, the dev preview and the click
 * redirect all pick it up with no further changes.
 *
 * Kept separate from CampaignTemplateRegistry on purpose: that one serves the
 * admin Campaign builder, whose audience is registered users filtered by segment.
 * This one has no admin surface and no user rows behind it.
 */
class MarketingTemplateRegistry
{
    public const TECHNOLOGY_SOLUTIONS = 'technology-solutions';

    /**
     * @return array<string, array{
     *     label:string, description:string, subject:string,
     *     purpose:EmailPurpose, mail_class:class-string<Mailable>, cta_path:string
     * }>
     */
    public static function all(): array
    {
        return [
            self::TECHNOLOGY_SOLUTIONS => [
                'label'       => 'Technology Solutions',
                'description' => 'Websites, mobile apps, custom web applications and AI automation for CA firms.',
                'subject'     => 'Website, Automation & Digital Solutions for Chartered Accountants',
                'purpose'     => EmailPurpose::MARKETING,
                'mail_class'  => TechnologySolutionsMail::class,
                'cta_path'    => '/technology-solutions#contact',
            ],
        ];
    }

    /** @return array<int,string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** @throws InvalidArgumentException */
    public static function get(string $key): array
    {
        $all = self::all();
        if (!isset($all[$key])) {
            throw new InvalidArgumentException(
                "Unknown marketing template '{$key}'. Available: " . implode(', ', array_keys($all)) . '.'
            );
        }

        return $all[$key];
    }

    /** Subject line for a template (also what lands in `email_logs.subject`). */
    public static function subjectFor(string $key): string
    {
        return self::get($key)['subject'];
    }

    /** Mail class short name, as stored in `email_logs.template_name`. */
    public static function templateNameFor(string $key): string
    {
        return class_basename(self::get($key)['mail_class']);
    }

    /** Absolute, untracked CTA target (used by test sends + the click redirect). */
    public static function ctaUrlFor(string $key): string
    {
        $base = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/');

        return $base . self::get($key)['cta_path'];
    }

    /**
     * Reverse lookup for the signed click route: marketing sends have no
     * `campaigns` row, so the CTA destination is resolved from the Mail class
     * recorded in `email_logs.template_name`. Null = not a marketing email.
     */
    public static function ctaUrlForTemplateName(?string $templateName): ?string
    {
        if ($templateName === null || $templateName === '') {
            return null;
        }

        foreach (self::all() as $key => $t) {
            if (class_basename($t['mail_class']) === $templateName) {
                return self::ctaUrlFor($key);
            }
        }

        return null;
    }

    /**
     * Build the Mailable for a template.
     *
     * The subject is passed INTO the Mailable (never via ->subject() afterwards):
     * a Mailable that defines envelope() re-hydrates its subject from the envelope
     * at delivery time, which would silently discard an outside override.
     *
     * @param array{firm_name?:string, subject?:string, cta_url?:string, open_pixel_url?:?string} $ctx
     */
    public static function make(string $key, array $ctx = []): Mailable
    {
        self::get($key); // validate

        $firmName     = $ctx['firm_name'] ?? 'there';
        $subject      = $ctx['subject']   ?? self::subjectFor($key);
        $ctaUrl       = $ctx['cta_url']   ?? self::ctaUrlFor($key);
        $openPixelUrl = $ctx['open_pixel_url'] ?? null;

        return match ($key) {
            self::TECHNOLOGY_SOLUTIONS => new TechnologySolutionsMail(
                $firmName,
                $subject,
                $ctaUrl,
                $openPixelUrl,
            ),
        };
    }
}
