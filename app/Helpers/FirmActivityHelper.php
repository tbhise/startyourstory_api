<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single write path for the firm-facing Activity Center feed
 * (`firm_activities` table — separate from the admin-facing activity_logs).
 *
 * Call AFTER the host operation has succeeded (post-commit) so only real,
 * completed actions appear in the firm's timeline:
 *
 *   FirmActivityHelper::log($firm->id, FirmActivityHelper::JOB_POSTED,
 *       'Posted Job "' . $title . '"');
 *
 * Controllers must never insert into firm_activities directly. The insert is
 * wrapped so a failure can NEVER break the host operation — an action that
 * succeeded must still succeed even if its activity row is lost.
 *
 * $firmId is firm_profiles.id (NOT users.id) — the same convention as
 * jobs.firm_id / recruiter_actions.firm_id. firm_name is never stored;
 * resolve it through the firm relationship when displaying.
 */
class FirmActivityHelper
{
    public const JOB_POSTED            = 'job_posted';
    public const JOB_EDITED            = 'job_edited';
    public const INTERVIEW_INVITE_SENT = 'interview_invite_sent';
    public const INTERVIEW_SCHEDULED   = 'interview_scheduled';
    public const PREMIUM_PURCHASED     = 'premium_purchased';
    public const PROFILE_COMPLETED     = 'profile_completed';
    public const PROFILE_VIEWED        = 'profile_viewed';

    /**
     * Record one activity row. Never throws.
     *
     * @param int|string|null $firmId      firm_profiles.id of the acting firm
     * @param string          $action      one of the class constants
     * @param string          $description human-readable line for the timeline
     */
    public static function log(int|string|null $firmId, string $action, string $description): void
    {
        try {
            if ($firmId === null || (int) $firmId <= 0) {
                return; // nothing meaningful to attribute the action to
            }

            DB::table('firm_activities')->insert([
                'firm_id'     => (int) $firmId,
                'action'      => $action,
                'description' => mb_substr($description, 0, 500),
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            // Best-effort logging: never let a feed insert surface to the caller.
            Log::warning('FirmActivityHelper::log failed: ' . $e->getMessage(), [
                'firm_id' => $firmId,
                'action'  => $action,
            ]);
        }
    }
}
