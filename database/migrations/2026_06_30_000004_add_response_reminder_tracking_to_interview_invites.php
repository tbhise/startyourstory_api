<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracking columns for the "student pending interview-invite response" reminders
 * (SendInterviewResponseReminderJob). Kept minimal — a single counter plus the
 * timestamp of the last reminder — mirroring the applications.reminder_*_sent_at
 * convention. Reminders escalate 24h / 72h / 5d off interview_invites.invited_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_invites', function (Blueprint $table) {
            // 0..3 — how many response reminders have been sent for this invite.
            $table->unsignedTinyInteger('response_reminders_sent')->default(0)->after('responded_at');
            $table->timestamp('last_response_reminder_at')->nullable()->after('response_reminders_sent');
        });
    }

    public function down(): void
    {
        Schema::table('interview_invites', function (Blueprint $table) {
            $table->dropColumn(['response_reminders_sent', 'last_response_reminder_at']);
        });
    }
};
