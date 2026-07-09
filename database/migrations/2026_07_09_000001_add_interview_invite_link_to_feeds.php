<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Link the firm-facing feeds (Activity Center + Notification bell) to the
 * interview_invites row they were raised for, so those pages can resolve the
 * invite's CURRENT status on read and render the right state — a "Schedule
 * Interview" CTA while the candidate has accepted-but-not-scheduled, or a final
 * "Interview Invitation Rejected" state once the candidate declines. This keeps
 * a SINGLE scheduling implementation (ScheduleInterviewDialog +
 * /interview-invites/{id}/schedule) reachable from every entry point without
 * duplicating any interview state onto the feed rows themselves.
 *
 * Nullable: only invite-related rows carry a link; every other activity /
 * notification simply has NULL and renders exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('firm_activities') && !Schema::hasColumn('firm_activities', 'interview_invite_id')) {
            Schema::table('firm_activities', function ($table) {
                $table->unsignedBigInteger('interview_invite_id')->nullable()->after('action');
                $table->index('interview_invite_id');
            });
        }

        if (Schema::hasTable('notifications') && !Schema::hasColumn('notifications', 'interview_invite_id')) {
            Schema::table('notifications', function ($table) {
                $table->unsignedBigInteger('interview_invite_id')->nullable()->after('message');
                $table->index('interview_invite_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('firm_activities', 'interview_invite_id')) {
            Schema::table('firm_activities', function ($table) {
                $table->dropIndex(['interview_invite_id']);
                $table->dropColumn('interview_invite_id');
            });
        }
        if (Schema::hasColumn('notifications', 'interview_invite_id')) {
            Schema::table('notifications', function ($table) {
                $table->dropIndex(['interview_invite_id']);
                $table->dropColumn('interview_invite_id');
            });
        }
    }
};
