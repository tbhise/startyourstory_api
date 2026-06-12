<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Student account deletion (30-day soft delete).
     *
     * `is_deleted` already exists in the live schema (it is referenced by the
     * users index and by auth queries), so it is only added defensively if a
     * given environment is missing it. The two new columns track the deletion
     * grace window.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_deleted')) {
                $table->boolean('is_deleted')->default(false)->after('id');
            }
            if (!Schema::hasColumn('users', 'deletion_requested_at')) {
                $table->dateTime('deletion_requested_at')->nullable()->after('token_expires_at');
            }
            if (!Schema::hasColumn('users', 'scheduled_deletion_at')) {
                $table->dateTime('scheduled_deletion_at')->nullable()->after('deletion_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only drop the columns this migration introduced.
            // `is_deleted` predates this feature, so it is intentionally left intact.
            if (Schema::hasColumn('users', 'scheduled_deletion_at')) {
                $table->dropColumn('scheduled_deletion_at');
            }
            if (Schema::hasColumn('users', 'deletion_requested_at')) {
                $table->dropColumn('deletion_requested_at');
            }
        });
    }
};
