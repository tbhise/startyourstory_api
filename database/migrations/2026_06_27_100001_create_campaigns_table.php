<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaign runs (re-engagement + future bulk-mail types).
 *
 * One row per executed campaign — captures the filter set, who ran it, when, and
 * the live counters. Replaces the previous fire-and-forget command (no run record).
 * Recipients still log to the shared `email_logs` table (now linked via campaign_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_type', 50)->default('reengagement');
            $table->string('campaign_name')->nullable();

            // Filter dimensions (also denormalised onto columns for the history table).
            $table->string('target_type', 20);                          // student | creator | firm
            $table->string('verification_status', 20)->default('all');  // all | verified | unverified
            $table->string('profile_completion_status', 20)->default('all'); // all | completed | incomplete
            $table->json('filters');                                    // NOT NULL — raw filter payload

            // Live counters.
            $table->unsignedInteger('eligible_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);        // reserved (needs open-pixel)
            $table->unsignedInteger('clicked_count')->default(0);       // bumped by the click-tracker

            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->enum('initiated_from', ['admin', 'cli', 'scheduler'])->default('admin');

            // Acting admin (nullable: CLI/scheduler runs have no admin). No DB-level FK,
            // matching the rest of the schema (email_logs / admin_activity_logs).
            $table->unsignedBigInteger('executed_by_admin_id')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Powers the 24h duplicate guard (same filter set) + the history listing.
            $table->index(
                ['campaign_type', 'target_type', 'verification_status', 'profile_completion_status', 'created_at'],
                'idx_campaign_filters_created'
            );
            $table->index('status');
            $table->index('executed_by_admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
