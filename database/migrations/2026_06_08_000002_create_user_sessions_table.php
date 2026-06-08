<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token', 80)->unique();
            $table->string('device_type', 20)->nullable();   // desktop | mobile | tablet
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('location', 255)->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_user_sessions_user_id');
            $table->index('token', 'idx_user_sessions_token');

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
