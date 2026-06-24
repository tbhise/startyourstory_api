<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 5 of the user-auth migration: drop the legacy hybrid-auth columns
 * users.api_token and users.token_expires_at.
 *
 * Background: all user authentication (students / firms / creators) now resolves
 * exclusively through the user_sessions table (cookie `auth_token`). The
 * AuthHelper resolver, ApiAuthMiddleware, AuthController and every previously
 * self-resolving controller no longer read or write these two columns. Admin
 * auth is unaffected (admin_users.api_token is a separate column, untouched).
 *
 * ⚠️ RUN ORDER (production-safe):
 *   1. Deploy the updated application code FIRST (the code that no longer
 *      references these columns).
 *   2. Verify all user flows (login, logout, dashboard, jobs, wallet, payments,
 *      impersonation) against the deployed code.
 *   3. THEN run this migration.
 *
 * Running this BEFORE the new code is live will break the old code paths that
 * still read/write api_token. These columns only hold ephemeral session tokens,
 * so dropping them simply forces a re-login — acceptable per the migration plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'api_token')) {
                $table->dropColumn('api_token');
            }
            if (Schema::hasColumn('users', 'token_expires_at')) {
                $table->dropColumn('token_expires_at');
            }
        });
    }

    public function down(): void
    {
        // Rollback restores the (empty) columns so the schema matches the old
        // shape. The token VALUES are not restored — they are ephemeral and were
        // intentionally discarded; users simply log in again.
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'api_token')) {
                $table->string('api_token', 255)->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'token_expires_at')) {
                $table->timestamp('token_expires_at')->nullable()->after('api_token');
            }
        });
    }
};
