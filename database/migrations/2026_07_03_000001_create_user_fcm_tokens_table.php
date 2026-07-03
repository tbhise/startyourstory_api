<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_fcm_tokens', function (Blueprint $table) {
            $table->id();
            // users.id (student or firm account). Deliberately separate from
            // admin_fcm_tokens so admin push infrastructure stays isolated.
            $table->unsignedBigInteger('user_id');
            // FCM registration token (one row per device — supports multiple
            // devices per user). Unique so re-registering the same device updates.
            $table->string('token', 512);
            $table->string('device_info', 255)->nullable(); // UA / platform label
            $table->dateTime('last_active_at')->nullable();
            $table->timestamps();

            $table->unique('token', 'uq_user_fcm_token');
            $table->index('user_id', 'idx_user_fcm_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_fcm_tokens');
    }
};
