<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lightweight business-activity log for firms and students.
 *
 * Records ONLY important business actions performed by firms/students
 * (job posted, interview invite sent/scheduled/accepted, content creation
 * posted/submitted, subscription purchased, wallet recharged) so the admin
 * panel can surface an activity feed later. Rows are written asynchronously
 * via LogActivityJob (queue) so logging never slows down — or breaks — the
 * host operation. Append-only by design: no update/delete path.
 *
 * actor_id is the users.id of the acting account (a firm or a student is a
 * users row with role 'firm' / 'student'); actor_type mirrors that role.
 * meta carries a small, action-specific JSON payload. No DB-level FKs, in
 * line with the rest of the schema (query-builder app).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('actor_type', ['firm', 'student']);
            $table->unsignedBigInteger('actor_id');
            $table->string('action_type', 64); // e.g. job_posted, wallet_recharged
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            // Hot paths for the future admin activity feed.
            $table->index(['actor_type', 'actor_id'], 'idx_al_actor');
            $table->index('action_type', 'idx_al_action');
            $table->index('created_at', 'idx_al_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
