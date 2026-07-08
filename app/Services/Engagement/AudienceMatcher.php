<?php

namespace App\Services\Engagement;

use App\Helpers\SubscriptionHelper;
use App\Services\Campaign\ReEngagementCampaignService;
use Illuminate\Support\Facades\DB;

/**
 * Decides whether a single user belongs to a campaign's audience.
 *
 * Role / email-verification / profile-completion are evaluated by REUSING the
 * email-campaign filter (ReEngagementCampaignService::buildEligibilityQuery) —
 * we simply constrain that query to the one user and check ->exists(), so there
 * is zero duplication of that logic. Only the NEW "plan" (premium/free)
 * dimension — which the email campaign system does not support — is added here.
 *
 * Audience shape (all keys optional; 'all' = no constraint):
 *   target_type               : all | student | creator | firm
 *   verification_status        : all | verified | unverified   (EMAIL verification)
 *   profile_completion_status  : all | completed | incomplete
 *   plan                       : all | premium | free
 */
class AudienceMatcher
{
    public function __construct(
        private ReEngagementCampaignService $reengagement = new ReEngagementCampaignService(),
    ) {}

    public function matches(array $audience, object $user): bool
    {
        $target = $audience['target_type'] ?? 'all';

        // For 'all' we still want to reuse the verification/profile filter, so map
        // the query's role to the user's OWN effective role — that makes the role
        // clause always pass for this user, leaving only verification + profile to
        // filter. For a specific role, a mismatch naturally yields no row.
        $roleForQuery = match ($target) {
            'firm'    => 'firm',
            'student' => 'student',
            'creator' => 'creator',
            default   => $user->role === 'firm'
                ? 'firm'
                : ($this->isCreator((int) $user->id) ? 'creator' : 'student'),
        };

        $inFilter = $this->reengagement->buildEligibilityQuery([
            'target_type'               => $roleForQuery,
            'verification_status'        => $audience['verification_status'] ?? 'all',
            'profile_completion_status'  => $audience['profile_completion_status'] ?? 'all',
        ])->where('users.id', (int) $user->id)->exists();

        if (!$inFilter) {
            return false;
        }

        // Plan dimension (the only piece not covered by the email filter).
        $plan = $audience['plan'] ?? 'all';
        if ($plan !== 'all') {
            $isPremium = $this->isPremium($user);
            if ($plan === 'premium' && !$isPremium) {
                return false;
            }
            if ($plan === 'free' && $isPremium) {
                return false;
            }
        }

        return true;
    }

    private function isCreator(int $userId): bool
    {
        return DB::table('student_profiles')
            ->where('user_id', $userId)
            ->where('looking_for', 'creator')
            ->exists();
    }

    /**
     * Premium detection mirrors the rest of the app: firms via an active,
     * non-expired firm_subscription (SubscriptionHelper); students via an
     * active, non-expired student_subscription.
     */
    private function isPremium(object $user): bool
    {
        if ($user->role === 'firm') {
            $firmId = DB::table('firm_profiles')->where('user_id', $user->id)->value('id');
            return $firmId ? SubscriptionHelper::isPremiumFirm((int) $firmId) : false;
        }

        return DB::table('student_subscriptions')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
