<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id');
            // FCM registration token (one row per device — supports multiple
            // devices per admin). Unique so re-registering the same device updates.
            $table->string('token', 512);
            $table->string('device_info', 255)->nullable(); // UA / platform label
            $table->dateTime('last_active_at')->nullable();
            $table->timestamps();

            $table->unique('token', 'uq_admin_fcm_token');
            $table->index('admin_user_id', 'idx_admin_fcm_admin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_fcm_tokens');
    }
};
