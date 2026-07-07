<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Employment Status feature — quick-lookup flag ONLY (admin filtering).
     * The full employment record lives in student_employment_history.
     * Completely independent of looking_for (Career Status) — do not merge.
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `student_profiles`
             ADD COLUMN `employment_status` ENUM('looking','joined')
             NOT NULL DEFAULT 'looking' AFTER `looking_for`"
        );
    }

    public function down(): void
    {
        Schema::table('student_profiles', function ($table) {
            $table->dropColumn('employment_status');
        });
    }
};
