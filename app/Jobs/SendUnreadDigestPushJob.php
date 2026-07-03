<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scheduled job — runs hourly.
 *
 * "Smart" unread-notifications digest push for students and firms.
 * Deliberately NOT a blind interval: a user gets a digest only when ALL hold:
 *
 *   1. they have at least one registered push device (user_fcm_tokens)
 *   2. unread notifications > 0 (bell notifications + recruiter_actions by role)
 *   3. inactive for > 6 hours (MAX(user_sessions.last_activity_at))
 *   4. last digest was > 8 hours ago  → hard cap of 2/day inside waking hours
 *   5. unread count has GROWN since the last digest (no re-nagging)
 *   6. local time is between 09:00 and 21:00 IST (also enforced here, not just
 *      by the scheduler, so delayed queue processing can't leak into the night)
 *
 * Throttle state lives in user_push_digest_state (one row per user).
 * Collapse tag `digest_{user}` → a newer digest replaces the older notification.
 * Push-only by design — no bell insert, no email; this job summarises the bell.
 */
class SendUnreadDigestPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    private const INACTIVE_HOURS   = 6;
    private const MIN_GAP_HOURS    = 8;
    private const QUIET_TZ         = 'Asia/Kolkata';
    private const SEND_HOUR_START  = 9;   // inclusive
    private const SEND_HOUR_END    = 21;  // exclusive

    public function handle(): void
    {
        $localHour = now(self::QUIET_TZ)->hour;
        if ($localHour < self::SEND_HOUR_START || $localHour >= self::SEND_HOUR_END) {
            return;
        }

        // Only users who can actually receive a push.
        $candidates = DB::table('user_fcm_tokens')
            ->join('users', 'users.id', '=', 'user_fcm_tokens.user_id')
            ->where('users.is_deleted', false)
            ->distinct()
            ->select('users.id', 'users.role')
            ->get();

        if ($candidates->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($candidates as $user) {
            try {
                // 3. Skip anyone active in the last 6 hours.
                $active = DB::table('user_sessions')
                    ->where('user_id', $user->id)
                    ->where('last_activity_at', '>=', $now->copy()->subHours(self::INACTIVE_HOURS))
                    ->exists();
                if ($active) {
                    continue;
                }

                // 4. Respect the minimum gap between digests.
                $state = DB::table('user_push_digest_state')->where('user_id', $user->id)->first();
                if ($state && $now->copy()->subHours(self::MIN_GAP_HOURS)->lt($state->last_sent_at)) {
                    continue;
                }

                // 2. Role-aware unread count + most recent unread title.
                [$unread, $latestTitle] = $this->unreadFor($user);
                if ($unread <= 0) {
                    continue;
                }

                // 5. Only when there is something NEW since the last digest.
                if ($state && $unread <= (int) $state->last_unread_count) {
                    continue;
                }

                SendUserPushJob::dispatch(
                    (int) $user->id,
                    "You have {$unread} unread " . ($unread === 1 ? 'notification' : 'notifications'),
                    $latestTitle
                        ? ($unread > 1 ? "{$latestTitle}, and " . ($unread - 1) . ' more' : $latestTitle)
                        : 'Open the app to catch up.',
                    $user->role === 'firm' ? '/firm-dashboard' : '/recruiter-actions',
                    [],
                    'digest_' . $user->id
                );

                DB::table('user_push_digest_state')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'last_sent_at'      => $now,
                        'last_unread_count' => $unread,
                        'updated_at'        => $now,
                        'created_at'        => $state->created_at ?? $now,
                    ]
                );
            } catch (Throwable $e) {
                // Isolate per-user failures.
                Log::error('Unread digest push failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Unread count + latest unread title for one user, matching what their
     * bell actually shows: `notifications` for everyone, plus role-scoped
     * `recruiter_actions`.
     *
     * @return array{0:int, 1:?string}
     */
    private function unreadFor(object $user): array
    {
        $bellUnread = DB::table('notifications')
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $bellLatest = DB::table('notifications')
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->value('title');

        $actionsQuery = null;
        if ($user->role === 'student') {
            $actionsQuery = DB::table('recruiter_actions')
                ->where('student_id', $user->id)
                ->whereIn('visible_to', ['student', 'both'])
                ->where('is_read', false);
        } elseif ($user->role === 'firm') {
            $firmId = DB::table('firm_profiles')->where('user_id', $user->id)->value('id');
            if ($firmId) {
                $actionsQuery = DB::table('recruiter_actions')
                    ->where('firm_id', $firmId)
                    ->whereIn('visible_to', ['firm', 'both'])
                    ->where('is_read', false);
            }
        }

        $actionsUnread = 0;
        $actionsLatest = null;
        if ($actionsQuery) {
            $actionsUnread = (clone $actionsQuery)->count();
            $actionsLatest = (clone $actionsQuery)->orderByDesc('created_at')->value('title');
        }

        // Prefer the recruiter-action title as the teaser (usually the richer event).
        return [$bellUnread + $actionsUnread, $actionsLatest ?: $bellLatest];
    }
}
