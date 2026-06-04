<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MessagingHelper
{
    // Free firm lifetime caps
    const FREE_LIFETIME_CONVERSATIONS = 3;
    const FREE_LIFETIME_REQUESTS_UNLOCKED = 3;

    // Premium firm monthly anti-spam cap (configurable per plan in future)
    const PREMIUM_MONTHLY_CONVERSATIONS = 100;

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
                'accept_direct_messages' => false,
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
        return $s ? (bool) $s->accept_direct_messages : false;
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
        $isPremium = SubscriptionHelper::isPremiumFirm($firmId);
        $limits    = self::getOrCreateLimits($firmId);

        if (!$isPremium) {
            if ($limits->lifetime_conversations_started >= self::FREE_LIFETIME_CONVERSATIONS) {
                return [
                    'allowed' => false,
                    'reason'  => 'free_limit_reached',
                    'message' => 'Upgrade to Premium to start more conversations.',
                ];
            }
        } else {
            if ($limits->monthly_conversations_started >= self::PREMIUM_MONTHLY_CONVERSATIONS) {
                return [
                    'allowed' => false,
                    'reason'  => 'monthly_limit_reached',
                    'message' => 'Monthly conversation limit reached. Limit resets next month.',
                ];
            }
        }

        return ['allowed' => true, 'reason' => null, 'message' => null];
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
    | Unread Count for a User
    |--------------------------------------------------------------------------
    */

    public static function getUnreadCount(int $userId, string $role): int
    {
        if ($role === 'student') {
            return (int) DB::table('messages as m')
                ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
                ->where('c.candidate_id', $userId)
                ->where('m.sender_type', 'firm')
                ->where('m.is_read', false)
                ->whereIn('c.status', ['active', 'pending'])
                ->count();
        }

        // firm: sender_type = candidate, and conversation belongs to this firm
        $firmProfile = DB::table('firm_profiles')->where('user_id', $userId)->first();
        if (!$firmProfile) return 0;

        return (int) DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('c.firm_id', $firmProfile->id)
            ->where('m.sender_type', 'candidate')
            ->where('m.is_read', false)
            ->whereIn('c.status', ['active', 'pending'])
            ->count();
    }
}
