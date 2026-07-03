<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_push_digest_state', function (Blueprint $table) {
            // One row per user — throttle state for the unread-notifications
            // digest push (see SendUnreadDigestPushJob).
            $table->unsignedBigInteger('user_id')->primary();
            $table->dateTime('last_sent_at');
            // Unread count at the time of the last digest — a new digest is only
            // sent when the count has GROWN since (no re-nagging about stale items).
            $table->unsignedInteger('last_unread_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_push_digest_state');
    }
};
