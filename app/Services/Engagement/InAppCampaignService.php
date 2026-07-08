<?php

namespace App\Services\Engagement;

use Illuminate\Support\Facades\DB;

/**
 * Runtime engine for the Engagement Hub in-app campaigns.
 *
 * Resolves the single highest-priority campaign a given user should see for a
 * given trigger, applying: status/date window, trigger match, audience
 * (AudienceMatcher), and per-user frequency capping (computed from the
 * in_app_campaign_events log). Also records interaction events.
 *
 * Frequency semantics (opt_out — the "Don't Ask Again" button — always wins):
 *   one_time     : shown at most once ever.
 *   every_login  : shown every trigger, unless "Later" was tapped in the last
 *                  LATER_SNOOZE_HOURS.
 *   cooldown     : shown again only after `cooldown_hours` since the last show
 *                  or "Later" (defaults to LATER_SNOOZE_HOURS if unset).
 *   never_again  : shown every trigger UNTIL the first explicit interaction
 *                  (primary/secondary/Later/opt_out), then never.
 */
class InAppCampaignService
{
    private const LATER_SNOOZE_HOURS = 24;

    public function __construct(
        private AudienceMatcher $audience = new AudienceMatcher(),
    ) {}

    /**
     * Highest-priority campaign this user should see now for $trigger, or null.
     * Records a 'shown' event for the returned campaign.
     *
     * $caps carries the CLIENT-only capability state the backend cannot observe
     * on its own — the source of truth for type-aware completion:
     *   'notif' : 'granted' | 'default' | 'denied' | 'unsupported' | ''
     *   'pwa'   : 'installed' | 'not_installed' | ''
     * A notification campaign is skipped once notifications are granted; a PWA
     * campaign once installed; a messages campaign requires BOTH (per spec).
     */
    public function resolveForUser(object $user, string $trigger, array $caps = []): ?object
    {
        $now = now();

        $candidates = DB::table('in_app_campaigns')
            ->where('status', 'active')
            ->where('trigger_type', $trigger)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get();

        foreach ($candidates as $c) {
            // Type-aware capability gate: skip when the campaign's real-world
            // condition means it should not show on this client (notification
            // already granted, PWA already installed, or a messages nudge whose
            // prerequisites aren't met). Eligibility changes automatically as the
            // client's permission / install state changes.
            if ($this->skipForCapabilities($c->type, $caps)) {
                continue;
            }

            $audience = $this->decodeAudience($c->audience);
            if (!$this->audience->matches($audience, $user)) {
                continue;
            }
            if (!$this->shouldShow($c, (int) $user->id)) {
                continue;
            }

            $this->logEvent((int) $c->id, (int) $user->id, 'shown');
            return $c;
        }

        return null;
    }

    /**
     * Should a capability campaign be skipped given the client's live state?
     *   notification → skip once notifications are granted (requirement done).
     *   pwa          → skip once the app is installed (requirement done).
     *   messages     → skip UNLESS notifications are granted AND the app is
     *                  installed (the nudge only targets already-equipped users).
     * Non-capability types (feature_announcement, unknown) are never gated here.
     */
    private function skipForCapabilities(string $type, array $caps): bool
    {
        $notif = $caps['notif'] ?? '';
        $pwa   = $caps['pwa'] ?? '';

        return match ($type) {
            'notification' => $notif === 'granted',
            'pwa'          => $pwa === 'installed',
            'messages'     => !($notif === 'granted' && $pwa === 'installed'),
            default        => false,
        };
    }

    /** Record one interaction event. Returns false if the action is unknown. */
    public function logEvent(int $campaignId, int $userId, string $action): bool
    {
        $allowed = ['shown', 'clicked_primary', 'clicked_secondary', 'later', 'opt_out', 'completed'];
        if (!in_array($action, $allowed, true)) {
            return false;
        }
        DB::table('in_app_campaign_events')->insert([
            'campaign_id' => $campaignId,
            'user_id'     => $userId,
            'action'      => $action,
            'created_at'  => now(),
        ]);
        return true;
    }

    private function shouldShow(object $campaign, int $userId): bool
    {
        $events = DB::table('in_app_campaign_events')
            ->where('campaign_id', $campaign->id)
            ->where('user_id', $userId)
            ->get(['action', 'created_at']);

        // Permanent suppression: "Don't Ask Again" (opt_out) or a recorded
        // completion (e.g. a messages campaign whose CTA was taken) — for every
        // frequency. Capability types (notification/PWA) are gated live in
        // skipForCapabilities instead, so they stay reactive.
        if ($events->whereIn('action', ['opt_out', 'completed'])->count()) {
            return false;
        }

        $nowTs = now()->timestamp;
        // Elapsed hours since the most recent event of the given kinds (null if
        // none). Computed from raw timestamps so it is independent of Carbon's
        // signed-vs-absolute diff behaviour across major versions (mirrors
        // SendInterviewResponseReminderJob).
        $hoursSince = function (array $actions) use ($events, $nowTs): ?float {
            $last = $events->whereIn('action', $actions)->max('created_at');
            return $last === null ? null : ($nowTs - strtotime((string) $last)) / 3600;
        };

        switch ($campaign->frequency) {
            case 'every_login':
                $since = $hoursSince(['later']);
                return $since === null || $since >= self::LATER_SNOOZE_HOURS;

            case 'cooldown':
                $hours = (int) ($campaign->cooldown_hours ?: self::LATER_SNOOZE_HOURS);
                $since = $hoursSince(['shown', 'later']);
                return $since === null || $since >= $hours;

            case 'never_again':
                // Shown until the first explicit interaction of any kind.
                return !$events->whereIn('action', ['clicked_primary', 'clicked_secondary', 'later'])->count();

            case 'one_time':
            default:
                return !$events->where('action', 'shown')->count();
        }
    }

    private function decodeAudience(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
