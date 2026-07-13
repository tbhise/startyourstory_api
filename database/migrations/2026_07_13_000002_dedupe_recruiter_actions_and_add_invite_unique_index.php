<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Duplicate Recruiter Action entries — root-cause fix (2026-07-13).
 *
 * Three code paths each INSERTed a fresh `interview_invite` row for the SAME
 * interview_invite_id (invite sent → scheduled → rescheduled). Because the feed
 * read query derives all displayed state from the JOINED `interview_invites`
 * row, those rows rendered as identical duplicate cards.
 *
 * This migration:
 *   1. Collapses existing duplicates — keeps the LATEST row per
 *      (interview_invite_id, action_type), deletes the older siblings.
 *   2. Adds a UNIQUE index so the duplicate can never be recreated.
 *
 * Scope is deliberately narrow: ONLY rows that share an interview_invite_id are
 * touched. Rows with interview_invite_id IS NULL (profile views, downloads,
 * shortlists, application-flow events, firm-visible tracking rows) are left
 * exactly as they are — no unrelated activity history is removed. MySQL allows
 * unlimited NULLs in a unique index, so those rows are unconstrained by it too.
 */
return new class extends Migration
{
    private const INDEX = 'uniq_ra_invite_action';

    public function up(): void
    {
        // ---------------------------------------------------------------
        // 1. Collapse existing duplicates (keep the latest row per invite).
        // ---------------------------------------------------------------
        // The survivor is the highest id in each group: it is the most recent
        // stage of the interview (e.g. the "scheduled" row inserted after the
        // original "pending" invite row), so it carries the correct title,
        // message and action_status for the invite's current state.
        $groups = DB::table('recruiter_actions')
            ->select('interview_invite_id', 'action_type', DB::raw('MAX(id) AS keep_id'), DB::raw('COUNT(*) AS total'))
            ->whereNotNull('interview_invite_id')
            ->groupBy('interview_invite_id', 'action_type')
            ->having('total', '>', 1)
            ->get();

        $deleted = 0;
        foreach ($groups as $group) {
            // Preserve read state: if the student had already read ANY row for
            // this invite, the survivor must not pop back up as unread.
            $wasRead = DB::table('recruiter_actions')
                ->where('interview_invite_id', $group->interview_invite_id)
                ->where('action_type', $group->action_type)
                ->where('is_read', 1)
                ->exists();

            $deleted += DB::table('recruiter_actions')
                ->where('interview_invite_id', $group->interview_invite_id)
                ->where('action_type', $group->action_type)
                ->where('id', '!=', $group->keep_id)
                ->delete();

            if ($wasRead) {
                DB::table('recruiter_actions')->where('id', $group->keep_id)->update(['is_read' => 1]);
            }
        }

        Log::info('[migration] recruiter_actions dedupe', [
            'duplicate_groups' => $groups->count(),
            'rows_deleted'     => $deleted,
        ]);

        // ---------------------------------------------------------------
        // 2. Make the duplicate structurally impossible.
        // ---------------------------------------------------------------
        Schema::table('recruiter_actions', function (Blueprint $table) {
            $table->unique(['interview_invite_id', 'action_type'], self::INDEX);
        });
    }

    public function down(): void
    {
        // Only the index is reversible — the deleted duplicate rows were
        // redundant renderings of a single interview and are not restored.
        Schema::table('recruiter_actions', function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });
    }
};
