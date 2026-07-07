<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Employment Status feature — one row per employment the student reports.
     * Exactly one row per user may have is_current = 1 (the active employment);
     * switching back to "looking" clears is_current on all rows but keeps the
     * history for future admin analytics.
     */
    public function up(): void
    {
        Schema::create('student_employment_history', function (Blueprint $table) {
            $table->id();
            // users.id of the student (project convention — student tables key by user_id).
            $table->unsignedBigInteger('user_id');
            $table->string('organization_name', 255);
            $table->string('designation', 255)->nullable();
            $table->date('joined_date');
            // Whether the student found this opportunity through StartYourStory.
            $table->boolean('joined_via_platform')->default(false);
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index('user_id', 'idx_seh_user');
            $table->index(['user_id', 'is_current'], 'idx_seh_user_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_employment_history');
    }
};
