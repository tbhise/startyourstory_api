<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class FreeActionsHelper
{
    const FREE_ACTIONS_LIMIT = 2;

    /*
    |--------------------------------------------------------------------------
    | Interview credit lifecycle (Phase 2, 2026-07-08)
    |--------------------------------------------------------------------------
    | A free interview credit is CONSUMED only when the student CONFIRMS a
    | scheduled interview — tracked by the explicit `interview_credit_consumed_at`
    | timestamp on interview_invites (invite flow) and applications (applications
    | flow). It is set once at confirmation and never cleared, so it survives
    | later status changes (e.g. confirmed -> completed) and reject/expiry simply
    | never set it (no refund logic needed).
    |
    | The quota is measured in DISTINCT students (a repeat interview with the same
    | candidate is free), deduped across BOTH flows.
    |
    | 'shortlisted' (Save Candidate) remains excluded — the shortlist feature is
    | disabled, so historical shortlist rows must not consume the free limit.
    */

    /** Distinct student ids whose interview credit is already consumed (both flows). */
    private static function consumedStudentIds(int $firmId): array
    {
        $invite = DB::table('interview_invites')
            ->where('firm_id', $firmId)
            ->whereNotNull('interview_credit_consumed_at')
            ->pluck('student_id')
            ->all();

        $apps = DB::table('applications')
            ->join('jobs', 'applications.job_id', '=', 'jobs.id')
            ->where('jobs.firm_id', $firmId)
            ->whereNotNull('applications.interview_credit_consumed_at')
            ->pluck('applications.student_id')
            ->all();

        return array_values(array_unique(array_map('intval', array_merge($invite, $apps))));
    }

    /**
     * Distinct student ids with an interview awaiting the student's confirmation
     * (scheduled but not yet confirmed / rejected / expired), credit not yet
     * consumed. These are "in flight" — they count toward the scheduling gate so
     * a firm cannot queue more interviews than its limit could ever confirm.
     */
    private static function pendingStudentIds(int $firmId): array
    {
        $invite = DB::table('interview_invites')
            ->where('firm_id', $firmId)
            ->where('interview_status', 'scheduled')
            ->whereNull('interview_credit_consumed_at')
            ->where(function ($q) {
                $q->whereNull('student_interview_response')
                    ->orWhere('student_interview_response', 'Pending');
            })
            ->pluck('student_id')
            ->all();

        // Applications flow: awaiting the student's response — either the initial
        // 'Interview Requested' or 'Interview Scheduled' (set when the firm accepts
        // a reschedule; see JobsController@acceptReschedule).
        $apps = DB::table('applications')
            ->join('jobs', 'applications.job_id', '=', 'jobs.id')
            ->where('jobs.firm_id', $firmId)
            ->whereIn('applications.recruiter_status', ['Interview Requested', 'Interview Scheduled'])
            ->where('applications.student_interview_response', 'Pending')
            ->whereNull('applications.interview_credit_consumed_at')
            ->pluck('applications.student_id')
            ->all();

        return array_values(array_unique(array_map('intval', array_merge($invite, $apps))));
    }

    /**
     * Consumed-credit breakdown. `total` is the DISTINCT-student count across
     * both flows (the number that counts against the free limit).
     */
    public static function getUsedCount(int $firmId): array
    {
        $inviteConsumed = DB::table('interview_invites')
            ->where('firm_id', $firmId)
            ->whereNotNull('interview_credit_consumed_at')
            ->distinct('student_id')
            ->count('student_id');

        $appConsumed = DB::table('applications')
            ->join('jobs', 'applications.job_id', '=', 'jobs.id')
            ->where('jobs.firm_id', $firmId)
            ->whereNotNull('applications.interview_credit_consumed_at')
            ->distinct('applications.student_id')
            ->count('applications.student_id');

        $total = count(self::consumedStudentIds($firmId)); // deduped across flows

        return [
            'saved'      => 0, // shortlist feature disabled
            'invites'    => (int) $inviteConsumed,
            'interviews' => (int) $appConsumed,
            'total'      => $total,
        ];
    }

    public static function getRemainingFreeActions(int $firmId): int
    {
        $used = self::getUsedCount($firmId);
        return max(0, self::FREE_ACTIONS_LIMIT - $used['total']);
    }

    /**
     * Generic free-action gate (still used by the disabled shortlist feature).
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

    /**
     * Scheduling gate (Phase 2). A non-premium firm may schedule an interview
     * for $studentId when either:
     *   • the candidate is already committed (consumed OR in-flight) — a
     *     reschedule / repeat is always free, OR
     *   • the firm has fewer than LIMIT distinct committed candidates.
     * This blocks a firm from queuing more pending interviews than its free
     * limit could ever confirm (the chosen "in-flight + confirmed" gate).
     *
     * Returns ['allowed' => bool, 'remaining' => int, 'message' => string|null].
     */
    public static function canScheduleInterview(int $firmId, ?int $studentId = null): array
    {
        if (SubscriptionHelper::isPremiumFirm($firmId)) {
            return ['allowed' => true, 'remaining' => PHP_INT_MAX, 'message' => null];
        }

        $committed = array_values(array_unique(array_merge(
            self::consumedStudentIds($firmId),
            self::pendingStudentIds($firmId),
        )));

        if ($studentId !== null && in_array((int) $studentId, $committed, true)) {
            return ['allowed' => true, 'remaining' => max(0, self::FREE_ACTIONS_LIMIT - count($committed)), 'message' => null];
        }

        if (count($committed) >= self::FREE_ACTIONS_LIMIT) {
            return [
                'allowed'   => false,
                'remaining' => 0,
                'message'   => 'You have reached your free interview limit. Upgrade to Premium for unlimited interviews.',
            ];
        }

        return ['allowed' => true, 'remaining' => self::FREE_ACTIONS_LIMIT - count($committed), 'message' => null];
    }

    public static function getStatus(int $firmId): array
    {
        $isPremium = SubscriptionHelper::isPremiumFirm($firmId);
        $used      = self::getUsedCount($firmId);
        $remaining = $isPremium ? PHP_INT_MAX : max(0, self::FREE_ACTIONS_LIMIT - $used['total']);

        // Can this firm schedule an interview with a NEW candidate right now?
        // (Reschedules of an already-committed candidate are always allowed —
        // enforced per-candidate in canScheduleInterview at the API layer.)
        $canScheduleNew = $isPremium
            || count(array_unique(array_merge(
                self::consumedStudentIds($firmId),
                self::pendingStudentIds($firmId),
            ))) < self::FREE_ACTIONS_LIMIT;

        return [
            'is_premium'       => $isPremium,
            'limit'            => self::FREE_ACTIONS_LIMIT,
            'used'             => $used['total'],
            'saved'            => $used['saved'],
            'invites'          => $used['invites'],
            'interviews'       => $used['interviews'],
            'remaining'        => $remaining,
            'can_schedule_new' => $canScheduleNew,
        ];
    }
}
