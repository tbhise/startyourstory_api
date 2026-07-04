<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MessagingHelper
{
    // Defaults for the admin-controlled messaging policy (overridable via
    // platform_settings: allow_free_firm_messaging, free_firm_conversation_limit).
    const DEFAULT_ALLOW_FREE_FIRM_MESSAGING = true;
    const DEFAULT_FREE_FIRM_CONVERSATION_LIMIT = 2;

    /*
    |--------------------------------------------------------------------------
    | Platform Messaging Policy (admin settings)
    |--------------------------------------------------------------------------
    */

    public static function allowFreeFirmMessaging(): bool
    {
        $v = DB::table('platform_settings')->where('key', 'allow_free_firm_messaging')->value('value');
        if ($v === null) {
            return self::DEFAULT_ALLOW_FREE_FIRM_MESSAGING;
        }
        return $v === 'true' || $v === '1';
    }

    public static function freeFirmConversationLimit(): int
    {
        $v = DB::table('platform_settings')->where('key', 'free_firm_conversation_limit')->value('value');
        if ($v === null || $v === '') {
            return self::DEFAULT_FREE_FIRM_CONVERSATION_LIMIT;
        }
        return max(0, (int) $v);
    }

    /**
     * Core policy: can a NEW conversation involving this firm be started?
     * Applies to BOTH directions (student->firm and firm->student).
     * - Premium firm: always (unlimited).
     * - Non-premium: only if free messaging is enabled AND under the lifetime limit.
     * Note (final business rule 2026-07-03): the lifetime limit counts ALL new
     * conversations involving the firm — firm-initiated AND student-initiated
     * both consume the free quota. Existing conversations are never affected.
     */
    public static function canStartNewConversation(int $firmId): array
    {
        if (SubscriptionHelper::isPremiumFirm($firmId)) {
            return ['allowed' => true, 'reason' => null, 'message' => null];
        }

        if (!self::allowFreeFirmMessaging()) {
            return [
                'allowed' => false,
                'reason'  => 'messaging_disabled',
                'message' => 'Messaging is not available on the free plan. Upgrade to Premium.',
            ];
        }

        $limit  = self::freeFirmConversationLimit();
        $limits = self::getOrCreateLimits($firmId);
        if ($limits->lifetime_conversations_started >= $limit) {
            return [
                'allowed' => false,
                'reason'  => 'free_limit_reached',
                'message' => 'Free conversation limit reached. Upgrade to Premium to start more conversations.',
            ];
        }

        return ['allowed' => true, 'reason' => null, 'message' => null];
    }

    /**
     * Student -> Firm initiation gate: same core policy as firm-initiated
     * (canStartNewConversation), but the student must never learn the firm's
     * premium/limit state, so all failures collapse to one generic message.
     * (The retired accept_direct_messages toggle is no longer consulted —
     * removed from the product 2026-07-03.)
     */
    public static function canStudentMessageFirm(int $firmId): array
    {
        $policy = self::canStartNewConversation($firmId);
        if (!$policy['allowed']) {
            return [
                'allowed' => false,
                'reason'  => 'not_accepting',
                'message' => 'Firm Not Accepting Direct Messages',
            ];
        }
        return ['allowed' => true, 'reason' => null, 'message' => null];
    }

    /**
     * Existing-conversation gate: once a conversation exists, messaging is
     * NEVER blocked by premium/limit policy (business rule) — only by the
     * conversation being closed or a participant no longer being active.
     */
    public static function canSendMessageInConversation(object $conv): array
    {
        if ($conv->status === 'blocked' || $conv->status === 'ignored') {
            return [
                'allowed' => false,
                'reason'  => 'conversation_closed',
                'message' => 'Cannot send message to this conversation',
            ];
        }

        $candidateActive = DB::table('users')
            ->where('id', $conv->candidate_id)
            ->where('is_deleted', false)
            ->exists();

        $firmUserId = DB::table('firm_profiles')->where('id', $conv->firm_id)->value('user_id');
        $firmActive = $firmUserId && DB::table('users')
            ->where('id', $firmUserId)
            ->where('is_deleted', false)
            ->exists();

        if (!$candidateActive || !$firmActive) {
            return [
                'allowed' => false,
                'reason'  => 'participant_inactive',
                'message' => 'This conversation is no longer available',
            ];
        }

        return ['allowed' => true, 'reason' => null, 'message' => null];
    }

    /*
    |--------------------------------------------------------------------------
    | Messaging Settings
    |--------------------------------------------------------------------------
    */

    // getOrCreateSettings removed 2026-07-03: the accept_direct_messages
    // toggle is retired from the product. The messaging_settings table stays
    // in the DB (untouched) but the app no longer reads or writes it.

    /*
    |--------------------------------------------------------------------------
    | Messaging Limits
    |--------------------------------------------------------------------------
    */

    public static function getOrCreateLimits(int $firmId): object
    {
        $limits = DB::table('messaging_limits')->where('firm_id', $firmId)->first();
        if (!$limits) {
            $now = Carbon::now();
            DB::table('messaging_limits')->insert([
                'firm_id'                         => $firmId,
                'lifetime_conversations_started'  => 0,
                'lifetime_requests_unlocked'      => 0,
                'monthly_conversations_started'   => 0,
                'current_period_start'            => $now->copy()->startOfMonth()->toDateString(),
                'current_period_end'              => $now->copy()->endOfMonth()->toDateString(),
                'created_at'                      => now(),
                'updated_at'                      => now(),
            ]);
            $limits = DB::table('messaging_limits')->where('firm_id', $firmId)->first();
        }

        // Roll over monthly counter if period has elapsed
        $limits = self::maybeRolloverPeriod($firmId, $limits);
        return $limits;
    }

    private static function maybeRolloverPeriod(int $firmId, object $limits): object
    {
        if (Carbon::parse($limits->current_period_end)->isPast()) {
            $now = Carbon::now();
            DB::table('messaging_limits')->where('firm_id', $firmId)->update([
                'monthly_conversations_started' => 0,
                'current_period_start'          => $now->copy()->startOfMonth()->toDateString(),
                'current_period_end'            => $now->copy()->endOfMonth()->toDateString(),
                'updated_at'                    => now(),
            ]);
            $limits = DB::table('messaging_limits')->where('firm_id', $firmId)->first();
        }
        return $limits;
    }

    /*
    |--------------------------------------------------------------------------
    | Increment Counters
    |--------------------------------------------------------------------------
    | NOTE (final business rules 2026-07-03): incremented for EVERY new
    | conversation involving the firm — both firm-initiated and student-
    | initiated ones consume the free limit.
    | The request-unlock system (canFirmUnlockRequest / incrementRequestsUnlocked)
    | was removed entirely; the lifetime_requests_unlocked column remains in
    | the table but is no longer read or written.
    */

    public static function incrementConversationsStarted(int $firmId): void
    {
        DB::table('messaging_limits')->where('firm_id', $firmId)->update([
            'lifetime_conversations_started' => DB::raw('lifetime_conversations_started + 1'),
            'monthly_conversations_started'  => DB::raw('monthly_conversations_started + 1'),
            'updated_at'                     => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Denormalized counter maintenance (Phase 2 — scalability)
    |--------------------------------------------------------------------------
    | conversations carries last_message_* + per-side unread counters so the
    | unread badge and conversation list never scan the messages table.
    */

    /**
     * Called whenever a message is persisted: refresh the conversation's
     * last-message snapshot and bump the RECIPIENT's unread counter.
     * $senderType is 'candidate' or 'firm'.
     */
    public static function applyMessageSent(int $conversationId, string $senderType, int $messageId, string $message): void
    {
        // candidate sends -> firm has a new unread; firm sends -> candidate does.
        $recipientCol = $senderType === 'candidate' ? 'firm_unread_count' : 'candidate_unread_count';

        DB::table('conversations')->where('id', $conversationId)->update([
            'last_message_at'           => now(),
            'last_message_id'           => $messageId,
            'last_message_preview'      => mb_substr($message, 0, 255),
            'last_message_sender_type'  => $senderType,
            $recipientCol               => DB::raw($recipientCol . ' + 1'),
            'updated_at'                => now(),
        ]);
    }

    /**
     * Called when a viewer reads a conversation: zero THEIR unread counter.
     * $readerRole is 'student' (candidate side) or 'firm'.
     */
    public static function applyConversationRead(int $conversationId, string $readerRole): void
    {
        $col = $readerRole === 'student' ? 'candidate_unread_count' : 'firm_unread_count';
        DB::table('conversations')->where('id', $conversationId)->update([
            $col         => 0,
            'updated_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Unread Count for a User (counter-based — no messages scan)
    |--------------------------------------------------------------------------
    */

    public static function getUnreadCount(int $userId, string $role): int
    {
        if ($role === 'student') {
            return (int) DB::table('conversations')
                ->where('candidate_id', $userId)
                ->whereIn('status', ['active', 'pending'])
                ->sum('candidate_unread_count');
        }

        $firmProfile = DB::table('firm_profiles')->where('user_id', $userId)->first();
        if (!$firmProfile) return 0;

        return (int) DB::table('conversations')
            ->where('firm_id', $firmProfile->id)
            ->whereIn('status', ['active', 'pending'])
            ->sum('firm_unread_count');
    }
}
