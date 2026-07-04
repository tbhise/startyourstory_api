<?php

namespace App\Jobs;

use App\Http\Controllers\API\MessagingController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fallback flush for chat pushes suppressed by the active-recipient gate.
 *
 * Problem this closes: pushToPeer suppresses pushes while the recipient is
 * active and relies on the NEXT message event to flush the aggregate. If the
 * recipient closes the app right after a suppression and no further message
 * arrives, that message is never pushed, never emailed (reply emails are
 * removed by design) and never digested (the digest counts bell/recruiter
 * actions, not chat) — silent until they reopen the app.
 *
 * Scheduled by pushToPeer with a PUSH_FLUSH_DELAY_SECONDS delay, at most once
 * per conversation+recipient burst (atomic Cache::add on msg_push_flush_*).
 * At fire time it re-checks reality and pushes ONLY when all hold:
 *   1. the recipient had NO activity at/after the last suppressed message
 *      (activity after it means the open tab received it via Reverb);
 *   2. the conversation is still unread for them (DB counters, not cache);
 *   3. the aggregate counter is still pending (0 = an event push already
 *      flushed it);
 *   4. the shared per-conversation cooldown slot is free (atomic).
 * Payload, deep link and conv_{id} collapse tag match pushToPeer exactly.
 *
 * tries = 1 and a blanket try/catch: a fallback push must never retry-spam
 * or fail the queue.
 */
class FlushSuppressedMessagePushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $conversationId,
        public int $recipientId
    ) {}

    public function handle(): void
    {
        try {
            $convId = $this->conversationId;
            $rid    = $this->recipientId;

            // Release the dedupe flag first so a later burst can schedule anew
            // regardless of which branch below we exit through.
            Cache::forget("msg_push_flush_{$convId}_{$rid}");

            // 1. Activity re-check. Skip when the recipient used the app AT or
            // AFTER the last suppressed message — their open tab already got it
            // in realtime (Reverb + unread badge). Comparing against the
            // suppression moment (not a fixed window) is what makes the short
            // flush delay safe: someone who closed the app the second the
            // message arrived still gets this fallback.
            $suppressedAt = (int) Cache::get("msg_push_flush_ts_{$convId}_{$rid}", 0);
            $lastActivity = DB::table('user_sessions')->where('user_id', $rid)->max('last_activity_at');
            if ($lastActivity !== null) {
                $lastTs = Carbon::parse($lastActivity)->getTimestamp();
                if ($suppressedAt > 0 && $lastTs >= $suppressedAt) {
                    return;
                }
                // Timestamp key lost (cache eviction): fall back to the normal
                // active-suppression window.
                if ($suppressedAt === 0 && $lastTs >= now()->subSeconds(MessagingController::PUSH_ACTIVE_SUPPRESS_SECONDS)->getTimestamp()) {
                    return;
                }
            }

            // 2. Unread re-check from DB truth — reading the conversation
            // zeroes these counters, so a read message never re-pushes.
            $conv = DB::table('conversations')->where('id', $convId)->first();
            if (!$conv || in_array($conv->status, ['blocked', 'ignored'], true)) {
                return;
            }
            $isCandidate = (int) $conv->candidate_id === $rid;
            if (!$isCandidate) {
                // Safety: confirm the recipient really is the firm side.
                $firmUserId = DB::table('firm_profiles')->where('id', $conv->firm_id)->value('user_id');
                if ((int) $firmUserId !== $rid) {
                    return;
                }
            }
            $unread = (int) ($isCandidate ? $conv->candidate_unread_count : $conv->firm_unread_count);
            if ($unread <= 0) {
                return;
            }

            // 3. Pending aggregate — 0 means a later message event already
            // flushed the aggregate with a real push; nothing left to say.
            $countKey = "msg_push_agg_{$convId}_{$rid}";
            $pending  = (int) Cache::get($countKey, 0);
            if ($pending <= 0) {
                return;
            }

            // 4. Shared cooldown slot (atomic) — also guards the race with a
            // concurrent event-driven push.
            if (!Cache::add("msg_push_cd_{$convId}_{$rid}", 1, MessagingController::PUSH_COOLDOWN_SECONDS)) {
                return;
            }
            Cache::forget($countKey);
            Cache::forget("msg_push_flush_ts_{$convId}_{$rid}");

            $senderName = $isCandidate
                ? (DB::table('firm_profiles')->where('id', $conv->firm_id)->value('firm_name') ?: 'a firm')
                : (DB::table('users')->where('id', $conv->candidate_id)->value('name') ?: 'a candidate');

            // last_message_preview is the attachment-aware snapshot kept by
            // MessagingHelper::applyMessageSent — same source the bell uses.
            $preview = (string) ($conv->last_message_preview ?? '');
            $preview = mb_strlen($preview) > 80 ? mb_substr($preview, 0, 77) . '…' : $preview;

            $title = $pending > 1
                ? "{$pending} new messages from {$senderName}"
                : "New message from {$senderName}";

            Log::debug("FlushSuppressedMessagePushJob: dispatching fallback push (conv {$convId}, recipient {$rid}, pending {$pending})");
            SendUserPushJob::dispatch($rid, $title, $preview, '/messages', [], 'conv_' . $convId);
        } catch (Throwable $e) {
            Log::warning('FlushSuppressedMessagePushJob failed: ' . $e->getMessage());
        }
    }
}
