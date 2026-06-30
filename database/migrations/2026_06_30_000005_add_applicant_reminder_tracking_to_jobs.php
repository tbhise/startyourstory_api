<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Throttle column for the daily "firm has applicants awaiting review" reminder
 * (SendFirmApplicantReminderJob). A job is only re-reminded when this is NULL or
 * older than the cooldown window, preventing daily spam about the same posting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->timestamp('last_applicant_reminder_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('last_applicant_reminder_at');
        });
    }
};
