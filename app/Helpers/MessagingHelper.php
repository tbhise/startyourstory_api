<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MessagingHelper
{
    // Free firm lifetime cap for request-unlocks (unchanged).
    const FREE_LIFETIME_REQUESTS_UNLOCKED = 3;

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
     * Core policy: can this firm be part of a NEW conversation?
     * - Premium firm: always (unlimited).
     * - Non-premium: only if free messaging is enabled AND under the lifetime limit.
     * (Does NOT consider the accept_direct_messages toggle — that gates the
     *  student->firm direction only; see canStudentMessageFirm.)
     */
    public static function firmCanHaveNewConversation(int $firmId): array
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
     * Student -> Firm initiation gate: firm policy must allow a new conversation
     * AND the firm must accept direct messages. The student should never learn
     * the firm's premium/limit state, so all failures collapse to one message.
     */
    public static function canStudentMessageFirm(int $firmId): array
    {
        $policy = self::firmCanHaveNewConversation($firmId);
        if (!$policy['allowed'] || !self::acceptsDirectMessages($firmId)) {
            return [
                'allowed' => false,
                'reason'  => 'not_accepting',
                'message' => 'Not Accepting Direct Messages',
            ];
        }
        return ['allowed' => true, 'reason' => null, 'message' => null];
    }

    /*
    |--------------------------------------------------------------------------
    | Messaging Settings
    |--------------------------------------------------------------------------
    */

    public static function getOrCreateSettings(int $firmId): object
    {
        $settings = DB::table('messaging_settings')->where('firm_id', $firmId)->first();
        if (!$settings) {
            DB::table('messaging_settings')->insert([
                'firm_id'               => $firmId,
                'accept_direct_messages' => true,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
            $settings = DB::table('messaging_settings')->where('firm_id', $firmId)->first();
        }
        return $settings;
    }

    public static function acceptsDirectMessages(int $firmId): bool
    {
        $s = DB::table('messaging_settings')->where('firm_id', $firmId)->first();
        return $s ? (bool) $s->accept_direct_messages : true;
    }

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
    | Can Firm Start New Conversation?
    |--------------------------------------------------------------------------
    */

    public static function canFirmStartConversation(int $firmId): array
    {
        // Firm-initiated: gated purely by the firm's own messaging policy
        // (premium = unlimited; free = enabled & under the admin limit).
        // The accept_direct_messages toggle does NOT block a firm's own outreach.
        return self::firmCanHaveNewConversation($firmId);
    }

    /*
    |--------------------------------------------------------------------------
    | Can Free Firm Unlock Request?
    |--------------------------------------------------------------------------
    */

    public static function canFirmUnlockRequest(int $firmId): bool
    {
        if (SubscriptionHelper::isPremiumFirm($firmId)) {
            return true;
        }
        $limits = self::getOrCreateLimits($firmId);
        return $limits->lifetime_requests_unlocked < self::FREE_LIFETIME_REQUESTS_UNLOCKED;
    }

    /*
    |--------------------------------------------------------------------------
    | Increment Counters
    |--------------------------------------------------------------------------
    */

    public static function incrementConversationsStarted(int $firmId): void
    {
        DB::table('messaging_limits')->where('firm_id', $firmId)->update([
            'lifetime_conversations_started' => DB::raw('lifetime_conversations_started + 1'),
            'monthly_conversations_started'  => DB::raw('monthly_conversations_started + 1'),
            'updated_at'                     => now(),
        ]);
    }

    public static function incrementRequestsUnlocked(int $firmId): void
    {
        DB::table('messaging_limits')->where('firm_id', $firmId)->update([
            'lifetime_requests_unlocked' => DB::raw('lifetime_requests_unlocked + 1'),
            'updated_at'                 => now(),
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
