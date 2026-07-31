<?php

namespace App\Services\Campaign;

use App\Enums\EmailPurpose;
use App\Mail\ReEngagementMail;
use App\Mail\TechnologySolutionsLaunchMail;
use Illuminate\Mail\Mailable;
use InvalidArgumentException;

/**
 * Built-in email templates selectable from the admin Campaign builder.
 *
 * Single source of truth for "which campaign emails can an admin send": the label
 * shown in the dropdown, which target types the template is allowed for, the
 * default (editable) subject, the sender purpose, and where the tracked CTA click
 * should land. A campaign row stores the chosen key in `campaigns.campaign_type`,
 * so no new column is needed — the history "Type" column now reads as the template.
 *
 * Adding a template = one Mailable + one Blade view + one entry here (plus its arm
 * in make()). Nothing else in the campaign pipeline changes.
 */
class CampaignTemplateRegistry
{
    public const REENGAGEMENT         = 'reengagement';
    public const TECHNOLOGY_SOLUTIONS = 'technology-solutions-launch';

    /**
     * @return array<string, array{
     *     label:string, description:string, targets:array<int,string>,
     *     purpose:EmailPurpose, template_name:string,
     *     default_subject:?string, cta_path:string
     * }>
     */
    public static function all(): array
    {
        return [
            self::REENGAGEMENT => [
                'label'           => 'Re-engagement',
                'description'     => 'Segment-aware lifecycle nudge; copy and subject adapt to the recipient.',
                'targets'         => ['student', 'creator', 'firm'],
                'purpose'         => EmailPurpose::REENGAGEMENT,
                'template_name'   => 'ReEngagementMail',
                // null = subject is derived per recipient (see subjectFor()).
                'default_subject' => null,
                'cta_path'        => '/login',
            ],

            self::TECHNOLOGY_SOLUTIONS => [
                'label'           => 'Technology Solutions Launch',
                'description'     => 'Announces the Technology Solutions vertical — websites, mobile apps, custom web apps and AI automation.',
                'targets'         => ['firm'],
                'purpose'         => EmailPurpose::MARKETING,
                'template_name'   => 'TechnologySolutionsLaunchMail',
                'default_subject' => '🚀 Introducing Technology Solutions by Start Your Story',
                'cta_path'        => '/technology-solutions#contact',
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
            throw new InvalidArgumentException('template_key must be one of: ' . implode(', ', array_keys($all)) . '.');
        }

        return $all[$key];
    }

    /**
     * Validate a template key and assert it supports the chosen target type.
     *
     * @throws InvalidArgumentException
     */
    public static function assertSupports(string $key, string $targetType): void
    {
        $t = self::get($key);
        if (!in_array($targetType, $t['targets'], true)) {
            throw new InvalidArgumentException(
                "Template '{$t['label']}' can only be sent to: " . implode(', ', $t['targets']) . '.'
            );
        }
    }

    /**
     * Dropdown payload for the admin builder (no closures / enums — JSON-safe).
     *
     * @return array<int, array{key:string,label:string,description:string,targets:array<int,string>,default_subject:?string}>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::all() as $key => $t) {
            $out[] = [
                'key'             => $key,
                'label'           => $t['label'],
                'description'     => $t['description'],
                'targets'         => $t['targets'],
                'default_subject' => $t['default_subject'],
            ];
        }

        return $out;
    }

    /** Absolute, untracked CTA target for a template (used by test sends + the click redirect). */
    public static function ctaUrlFor(string $key): string
    {
        $base = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/');

        return $base . self::get($key)['cta_path'];
    }

    /**
     * Build the Mailable for a template.
     *
     * @param array{
     *     name:string, subject:string, cta_url:string, open_pixel_url?:?string,
     *     user_type?:string, verified?:bool, completed?:bool
     * } $ctx
     */
    public static function make(string $key, array $ctx): Mailable
    {
        self::get($key); // validate

        return match ($key) {
            self::REENGAGEMENT => new ReEngagementMail(
                $ctx['name'],
                $ctx['user_type'] ?? 'student',
                (bool) ($ctx['verified'] ?? true),
                (bool) ($ctx['completed'] ?? false),
                $ctx['subject'],
                $ctx['cta_url'],
                $ctx['open_pixel_url'] ?? null,
            ),

            self::TECHNOLOGY_SOLUTIONS => (new TechnologySolutionsLaunchMail(
                $ctx['name'],
                $ctx['cta_url'],
                $ctx['open_pixel_url'] ?? null,
            ))->subject($ctx['subject']),
        };
    }
}
