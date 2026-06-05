<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email', 255);
            $table->string('recipient_type', 20); // student | firm | admin | user
            $table->string('email_purpose', 100);
            $table->string('template_name', 100);
            $table->string('sender_identity', 100);
            $table->string('subject', 500);
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['email_purpose', 'status']);
            $table->index(['recipient_email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
