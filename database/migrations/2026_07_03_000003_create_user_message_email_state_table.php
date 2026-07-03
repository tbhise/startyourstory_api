<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_message_email_state', function (Blueprint $table) {
            // One row per user — throttle state for the unread-messages reminder
            // email (see SendUnreadMessagesEmailJob): max one email per 3 hours.
            $table->unsignedBigInteger('user_id')->primary();
            $table->dateTime('last_sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_message_email_state');
    }
};
