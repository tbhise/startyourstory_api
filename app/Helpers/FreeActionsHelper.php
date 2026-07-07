<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class FreeActionsHelper
{
    const FREE_ACTIONS_LIMIT = 2;

    /**
     * Count free actions used. Billable events (each counted as distinct
     * candidates so the same candidate twice isn't double-charged):
     *   - SCHEDULED invite-flow interviews (interview_invites.scheduled_at set)
     *   - interview_requested recruiter_actions (Schedule Interview from job
     *     applications — that flow schedules directly, with a date)
     *
     * Sending an interview invitation is UNLIMITED and never counted — the
     * quota is consumed only when the firm actually schedules the interview
     * (2026-07-07 change; previously the invite itself consumed an action).
     * scheduled_at is written once at scheduling and never cleared, so
     * completed/cancelled interviews keep counting, while pending, declined,
     * expired, cancelled-before-schedule, and accepted-but-unscheduled
     * invites never do.
     *
     * NOTE: 'shortlisted' (Save Candidate) is temporarily excluded — the
     * shortlist feature is disabled, so historical shortlist rows must not
     * consume the free limit. Re-add the recruiter_actions count for
     * 'shortlisted' if the feature comes back.
     */
    public static function getUsedCount(int $firmId): array
    {
        $saved = 0; // shortlist feature disabled

        $invites = (int) DB::table('interview_invites')
            ->where('firm_id', $firmId)
            ->whereNotNull('scheduled_at')
            ->distinct('student_id')
            ->count('student_id');

        $interviews = (int) DB::table('recruiter_actions')
            ->where('firm_id', $firmId)
            ->where('action_type', 'interview_requested')
            ->distinct('student_id')
            ->count('student_id');

        return [
            'saved'      => $saved,
            'invites'    => $invites,
            'interviews' => $interviews,
            'total'      => $saved + $invites + $interviews,
        ];
    }

    public static function getRemainingFreeActions(int $firmId): int
    {
        $used = self::getUsedCount($firmId);
        return max(0, self::FREE_ACTIONS_LIMIT - $used['total']);
    }

    /**
     * Returns ['allowed' => bool, 'remaining' => int, 'message' => string|null]
     */
    public static function canPerformFreeAction(int $firmId): array
    {
        if (SubscriptionHelper::isPremiumFirm($firmId)) {
            return ['allowed' => true, 'remaining' => PHP_INT_MAX, 'message' => null];
        }

        $remaining = self::getRemainingFreeActions($firmId);

        if ($remaining <= 0) {
            return [
                'allowed'   => false,
                'remaining' => 0,
                'message'   => 'You have used all your free actions. Upgrade to Premium for unlimited access.',
            ];
        }

        return ['allowed' => true, 'remaining' => $remaining, 'message' => null];
    }

    public static function getStatus(int $firmId): array
    {
        $isPremium = SubscriptionHelper::isPremiumFirm($firmId);
        $used      = self::getUsedCount($firmId);
        $remaining = $isPremium ? PHP_INT_MAX : max(0, self::FREE_ACTIONS_LIMIT - $used['total']);

        return [
            'is_premium' => $isPremium,
            'limit'      => self::FREE_ACTIONS_LIMIT,
            'used'       => $used['total'],
            'saved'      => $used['saved'],
            'invites'    => $used['invites'],
            'interviews' => $used['interviews'],
            'remaining'  => $remaining,
        ];
    }
}
