<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flags a user_sessions row as an admin impersonation session.
 *
 * is_impersonation = 1 marks the session as minted by an admin via "Login as User".
 * impersonated_by  = the admin_users.id that started it.
 *
 * Normal logins leave both at their defaults (0 / null), so existing auth is
 * completely unaffected. The read-only middleware and /me only special-case
 * sessions where is_impersonation = 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_sessions', 'is_impersonation')) {
                $table->boolean('is_impersonation')->default(false)->after('location');
            }
            if (!Schema::hasColumn('user_sessions', 'impersonated_by')) {
                $table->unsignedBigInteger('impersonated_by')->nullable()->after('is_impersonation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('user_sessions', 'impersonated_by')) {
                $table->dropColumn('impersonated_by');
            }
            if (Schema::hasColumn('user_sessions', 'is_impersonation')) {
                $table->dropColumn('is_impersonation');
            }
        });
    }
};
