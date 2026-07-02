<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class FreeActionsHelper
{
    const FREE_ACTIONS_LIMIT = 2;

    /**
     * Count free actions used. Billable action types (each counted as distinct
     * candidates so the same candidate in two jobs isn't double-charged):
     *   - interview_invite     (Invite to Interview)
     *   - interview_requested  (Schedule Interview from job applications)
     *
     * NOTE: 'shortlisted' (Save Candidate) is temporarily excluded — the
     * shortlist feature is disabled, so historical shortlist rows must not
     * consume the free limit. Re-add $countByType('shortlisted') if the
     * feature comes back.
     */
    public static function getUsedCount(int $firmId): array
    {
        $countByType = function (string $type) use ($firmId): int {
            return (int) DB::table('recruiter_actions')
                ->where('firm_id', $firmId)
                ->where('action_type', $type)
                ->distinct('student_id')
                ->count('student_id');
        };

        $saved      = 0; // shortlist feature disabled; was $countByType('shortlisted')
        $invites    = $countByType('interview_invite');
        $interviews = $countByType('interview_requested');

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
