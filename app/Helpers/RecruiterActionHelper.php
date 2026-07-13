<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single write path for the `recruiter_actions` feed (the student-facing
 * "Application Activity" timeline + the firm-facing tracking rows).
 *
 * Controllers must never INSERT into recruiter_actions directly — every write
 * goes through one of the four entry points below. This mirrors the existing
 * FirmActivityHelper contract.
 *
 * ---------------------------------------------------------------------------
 * The invite rule (root cause of the duplicate-timeline bug, 2026-07-13)
 * ---------------------------------------------------------------------------
 * An interview invite has exactly ONE row in this table for its whole life.
 * invite → schedule → reschedule → accept/reject/complete/cancel all UPDATE
 * that one row rather than inserting a new one.
 *
 * This is required (not merely tidy) because the read query in
 * JobsController::getRecruiterActions LEFT JOINs `interview_invites` and
 * derives every displayed field (invite_status, interview_status,
 * interview_date, student_response, reschedule_count) from the JOINED invite —
 * never from the action row. Two rows pointing at one invite therefore render
 * as two byte-identical cards with two identical Accept/Reject button sets.
 *
 * A unique index on (interview_invite_id, action_type) enforces this at the DB
 * level. MySQL permits unlimited NULLs in a unique index, so non-invite rows
 * (interview_invite_id IS NULL) are completely unaffected by it.
 *
 * All methods are best-effort and NEVER throw: a feed row is a side effect, and
 * losing one must never fail the interview/application operation that caused it.
 */
class RecruiterActionHelper
{
    /* Interview-invite lifecycle (one row per invite, keyed by invite id) */
    public const INTERVIEW_INVITE = 'interview_invite';

    /* Application-flow actions (keyed by application id) */
    public const SHORTLISTED         = 'shortlisted';
    public const REJECTED            = 'rejected';
    public const SELECTED            = 'selected';
    public const INTERVIEW_REQUESTED = 'interview_requested';
    public const RESCHEDULE_ACCEPTED = 'reschedule_accepted';

    /* Candidate-sourcing actions (keyed by firm+student) */
    public const PROFILE_VIEWED       = 'profile_viewed';
    public const CANDIDATE_SAVED      = 'candidate_saved';
    public const CANDIDATE_REJECTED   = 'candidate_rejected';
    public const RESUME_DOWNLOADED    = 'resume_downloaded';
    public const MARKSHEET_DOWNLOADED = 'marksheet_downloaded';

    /** Default dedupe window for repeatable actions (views, downloads, saves). */
    public const DEFAULT_DEDUPE_HOURS = 24;

    /**
     * Plain insert — for genuinely new, non-repeatable events (e.g. the
     * firm-visible row written when a student responds to an interview).
     */
    public static function log(array $attributes): void
    {
        self::guard(function () use ($attributes) {
            DB::table('recruiter_actions')->insert(self::normalize($attributes));
        }, $attributes);
    }

    /**
     * Insert ONLY if no equivalent row already exists.
     *
     * $uniqueBy names the columns that make a row "the same event" (e.g.
     * firm_id + student_id + application_id + action_type). When $withinHours is
     * given, the match is additionally limited to that recent window — which is
     * what stops repeat resume/marksheet downloads and profile views from
     * spamming the timeline, while still allowing a genuinely new occurrence
     * later on to be recorded as real history.
     *
     * Passing $withinHours = null means "once, ever" (used by the application
     * status flow: a candidate is shortlisted once per application).
     */
    public static function logOnce(array $attributes, array $uniqueBy, ?int $withinHours = null): void
    {
        self::guard(function () use ($attributes, $uniqueBy, $withinHours) {
            $row = self::normalize($attributes);

            $query = DB::table('recruiter_actions');
            foreach ($uniqueBy as $column) {
                $value = $row[$column] ?? null;
                $value === null
                    ? $query->whereNull($column)
                    : $query->where($column, $value);
            }
            if ($withinHours !== null) {
                $query->where('created_at', '>=', now()->subHours($withinHours));
            }

            if ($query->exists()) {
                return; // already recorded — not a new event
            }

            DB::table('recruiter_actions')->insert($row);
        }, $attributes);
    }

    /**
     * Upsert THE single row for an interview invite.
     *
     * First call (invite sent / direct schedule) inserts; every later stage
     * (scheduled, rescheduled) updates the same row in place.
     *
     * created_at is refreshed so the interview surfaces at the top of the
     * chronological timeline on each new development, and is_read is reset so
     * the student is re-notified — which is exactly what the old
     * insert-a-new-row behaviour achieved, minus the duplicate card.
     */
    public static function logInvite(int|string|null $inviteId, array $attributes): void
    {
        self::guard(function () use ($inviteId, $attributes) {
            if ($inviteId === null || (int) $inviteId <= 0) {
                return;
            }

            $row = self::normalize($attributes);
            $row['interview_invite_id'] = (int) $inviteId;
            $row['action_type']         = self::INTERVIEW_INVITE;
            $row['created_at']          = $row['created_at'] ?? now();
            $row['is_read']             = 0;

            DB::table('recruiter_actions')->updateOrInsert(
                [
                    'interview_invite_id' => (int) $inviteId,
                    'action_type'         => self::INTERVIEW_INVITE,
                ],
                $row,
            );
        }, $attributes);
    }

    /**
     * Move an invite's existing row to a new status WITHOUT resurfacing it.
     *
     * Used for transitions the student themselves performed (confirm / reject /
     * request reschedule) and for terminal admin/system transitions (completed,
     * cancelled, expired) — the row's position and read state are left alone
     * because there is nothing new for the student to read.
     */
    public static function updateInviteStatus(int|string|null $inviteId, string $status, array $extra = []): void
    {
        self::guard(function () use ($inviteId, $status, $extra) {
            if ($inviteId === null || (int) $inviteId <= 0) {
                return;
            }

            DB::table('recruiter_actions')
                ->where('interview_invite_id', (int) $inviteId)
                ->where('action_type', self::INTERVIEW_INVITE)
                ->update(array_merge($extra, ['action_status' => $status]));
        }, ['invite_id' => $inviteId, 'status' => $status]);
    }

    /** Trim free-text columns to their schema limits so a long title can't fail the insert. */
    private static function normalize(array $attributes): array
    {
        if (isset($attributes['title'])) {
            $attributes['title'] = mb_substr((string) $attributes['title'], 0, 255);
        }
        if (!array_key_exists('created_at', $attributes)) {
            $attributes['created_at'] = now();
        }
        return $attributes;
    }

    /** Best-effort execution: log and swallow, never surface to the caller. */
    private static function guard(callable $fn, array $context = []): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::warning('RecruiterActionHelper write failed: ' . $e->getMessage(), [
                'action_type' => $context['action_type'] ?? null,
                'firm_id'     => $context['firm_id'] ?? null,
                'student_id'  => $context['student_id'] ?? null,
            ]);
        }
    }
}
