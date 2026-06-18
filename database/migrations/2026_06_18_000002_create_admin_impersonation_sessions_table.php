<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for admin "Login as User" (impersonation).
 *
 * One row per impersonation session. login_time is stamped on start, logout_time
 * is stamped when the admin exits (or the impersonation auth_token is logged out).
 * No FKs by project convention (admins live in admin_users; target in users).
 *
 * This is SEPARATE from admin_activity_logs because that table is append-only and
 * has no update path — impersonation needs a mutable logout_time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_impersonation_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');                 // admin_users.id (super_admin)
            $table->string('admin_name', 255)->nullable();
            $table->unsignedBigInteger('target_user_id');           // users.id being impersonated
            $table->string('target_role', 20);                      // student | firm
            $table->string('token', 80);                            // the issued user_sessions.token
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('login_time');
            $table->dateTime('logout_time')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('admin_id', 'idx_ais_admin');
            $table->index('target_user_id', 'idx_ais_target');
            $table->index('token', 'idx_ais_token');
            $table->index('logout_time', 'idx_ais_logout');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_impersonation_sessions');
    }
};
