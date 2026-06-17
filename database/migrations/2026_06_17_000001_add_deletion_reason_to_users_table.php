<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-recorded reason for an account deletion (firm or student).
     *
     * Added for the admin "Delete Firm" action, which requires a mandatory
     * reason. Nullable + guarded so it is safe on every environment and does not
     * affect existing soft-deleted rows.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'deletion_reason')) {
                $table->string('deletion_reason', 500)->nullable()->after('scheduled_deletion_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deletion_reason')) {
                $table->dropColumn('deletion_reason');
            }
        });
    }
};
