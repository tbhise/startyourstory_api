<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support tickets raised by students and firms.
 *
 * One row per ticket. ticket_no is the human-facing identifier (SYS-TKT-000001),
 * derived from the auto-increment id after insert. user_type mirrors users.role
 * ('student' | 'firm'). attachments holds a JSON array of uploaded files
 * ({path,url,name,mime,size}). The conversation lives in support_ticket_messages.
 * No DB-level FKs, matching the rest of the schema (query-builder app).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 30)->nullable()->unique();
            $table->unsignedBigInteger('user_id');
            $table->enum('user_type', ['student', 'firm']);
            $table->string('ticket_category', 100);
            $table->text('issue_brief');
            $table->json('attachments')->nullable();
            $table->enum('status', ['submitted', 'in_process', 'closed'])->default('submitted');
            $table->unsignedBigInteger('assigned_to_admin_id')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Hot paths: a user's own ticket list (newest first), admin filters.
            $table->index(['user_id', 'user_type', 'created_at'], 'idx_st_user');
            $table->index('status', 'idx_st_status');
            $table->index('assigned_to_admin_id', 'idx_st_assigned');
            $table->index('ticket_category', 'idx_st_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
