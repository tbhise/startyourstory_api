<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for the admin Interview Tracking + Joined Students list pages
     * (2026-07-17). Read-path only — no column/data changes. Both admin lists
     * filter on these date columns (whereNotNull / date-range) and previously
     * had no index covering them.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->index('interview_date', 'idx_app_interview_date');
        });
        Schema::table('interview_invites', function (Blueprint $table) {
            $table->index('interview_date', 'idx_ii_interview_date');
        });
        Schema::table('student_employment_history', function (Blueprint $table) {
            $table->index('joined_date', 'idx_seh_joined_date');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex('idx_app_interview_date');
        });
        Schema::table('interview_invites', function (Blueprint $table) {
            $table->dropIndex('idx_ii_interview_date');
        });
        Schema::table('student_employment_history', function (Blueprint $table) {
            $table->dropIndex('idx_seh_joined_date');
        });
    }
};
