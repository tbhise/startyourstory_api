<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a denormalized `last_login_at` timestamp to `users` and `admin_users`.
 *
 * Background (audit, 2026-06-29): student/firm logins are recorded reliably in
 * the append-only `login_history` table, but the admin Students/Firms listings
 * need a cheap, per-row "Last Login" value without a MAX(login_history) subquery
 * — and admins have NO login tracking at all. We therefore denormalize:
 *
 *   - users.last_login_at       → stamped in AuthController::login (alongside the
 *                                 existing login_history insert). `login_history`
 *                                 remains the source-of-truth audit trail.
 *   - admin_users.last_login_at → stamped in AdminController::login. Set EXPLICITLY
 *                                 (NOT reusing updated_at, which drifts on any edit).
 *
 * Both columns are indexed because the admin panel sorts (Recent/Oldest Login)
 * and filters (activity buckets) on them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->dateTime('last_login_at')->nullable()->after('email_verified_at');
                $table->index('last_login_at', 'idx_users_last_login_at');
            }
        });

        // admin_users lives outside the Laravel migration history (seeded via the
        // SQL dump), but Schema::table operates on the live table just fine.
        Schema::table('admin_users', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_users', 'last_login_at')) {
                $table->dateTime('last_login_at')->nullable()->after('is_active');
                $table->index('last_login_at', 'idx_admin_users_last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropIndex('idx_users_last_login_at');
                $table->dropColumn('last_login_at');
            }
        });

        Schema::table('admin_users', function (Blueprint $table) {
            if (Schema::hasColumn('admin_users', 'last_login_at')) {
                $table->dropIndex('idx_admin_users_last_login_at');
                $table->dropColumn('last_login_at');
            }
        });
    }
};
