<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * error_summary now holds the FULL raw (secret-redacted) exception message
     * instead of a 1000-char slice, so admins get the complete error from the
     * dashboard. Widen VARCHAR(1000) -> TEXT. The `stack` column already exists
     * as TEXT and is now populated by ErrorLogRecorder with the full trace.
     */
    public function up(): void
    {
        if (Schema::hasColumn('error_logs', 'error_summary')) {
            DB::statement('ALTER TABLE `error_logs` MODIFY `error_summary` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('error_logs', 'error_summary')) {
            // NOTE: rows longer than 1000 chars will be truncated on rollback.
            DB::statement('ALTER TABLE `error_logs` MODIFY `error_summary` VARCHAR(1000) NULL');
        }
    }
};
