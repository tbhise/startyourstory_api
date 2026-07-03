<?php

namespace App\Jobs;

use App\Mail\UnreadMessagesReminderMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Scheduled job — runs hourly.
 *
 * Unread-messages reminder EMAIL for students and firms. Replaces the old
 * per-message email (one mail per chat message): a user with unread messages
 * now receives at most ONE summary email every 3 hours, and only while the
 * messages remain unread — reading them stops the reminders automatically
 * (the denormalised conversations.*_unread_count drops to 0 on read).
 *
 * Push (pushToPeer) stays the realtime channel; this email is the slow
 * "you walked away" backstop. Throttle state: user_message_email_state.
 */
class SendUnreadMessagesEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    private const MIN_GAP_HOURS = 3;

    public function handle(): void
    {
        // Students: unread totals live on conversations.candidate_unread_count.
        $students = DB::table('conversations')
            ->whereIn('status', ['active', 'pending'])
            ->where('candidate_unread_count', '>', 0)
            ->groupBy('candidate_id')
            ->select(
                'candidate_id as user_id',
                DB::raw('SUM(candidate_unread_count) as unread'),
                DB::raw('COUNT(*) as convs')
            )
            ->get();

        // Firms: firm_unread_count, mapped to the owning user via firm_profiles.
        $firms = DB::table('conversations')
            ->join('firm_profiles', 'firm_profiles.id', '=', 'conversations.firm_id')
            ->whereIn('conversations.status', ['active', 'pending'])
            ->where('conversations.firm_unread_count', '>', 0)
            ->groupBy('firm_profiles.user_id')
            ->select(
                'firm_profiles.user_id as user_id',
                DB::raw('SUM(conversations.firm_unread_count) as unread'),
                DB::raw('COUNT(*) as convs')
            )
            ->get();

        $now = now();

        foreach ($students->concat($firms) as $row) {
            try {
                // Throttle: at most one reminder email per user per 3 hours.
                $state = DB::table('user_message_email_state')
                    ->where('user_id', $row->user_id)
                    ->first();
                if ($state && $now->copy()->subHours(self::MIN_GAP_HOURS)->lt($state->last_sent_at)) {
                    continue;
                }

                $user = DB::table('users')
                    ->where('id', $row->user_id)
                    ->where('is_deleted', false)
                    ->first();
                if (!$user || !$user->email) {
                    continue;
                }

                Mail::to($user->email)->queue(
                    new UnreadMessagesReminderMail($user, (int) $row->unread, (int) $row->convs)
                );

                DB::table('user_message_email_state')->updateOrInsert(
                    ['user_id' => $row->user_id],
                    [
                        'last_sent_at' => $now,
                        'updated_at'   => $now,
                        'created_at'   => $state->created_at ?? $now,
                    ]
                );
            } catch (Throwable $e) {
                // Isolate per-user failures.
                Log::error('Unread messages email reminder failed', [
                    'user_id' => $row->user_id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }
}
