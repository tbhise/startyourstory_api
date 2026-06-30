<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thread messages for a support ticket.
 *
 * sender_type is one of 'student' | 'firm' | 'admin' | 'system'. 'system' rows are
 * auto-generated status-change notes (sender_id NULL). attachment_path is a single
 * optional file stored on the public disk (URL derived as asset('storage/'.path)).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->enum('sender_type', ['student', 'firm', 'admin', 'system']);
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('message');
            $table->string('attachment_path', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            // Powers the chronological thread fetch for a ticket.
            $table->index(['ticket_id', 'created_at'], 'idx_stm_ticket');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};
