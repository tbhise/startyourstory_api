<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * error_summary now stores the RAW (secret-redacted) exception message so
     * admins can see what actually happened without opening laravel.log.
     * Widen 100 → 1000 chars. `message` stays the short, sanitized one-liner.
     */
    public function up(): void
    {
        if (Schema::hasColumn('error_logs', 'error_summary')) {
            DB::statement('ALTER TABLE `error_logs` MODIFY `error_summary` VARCHAR(1000) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('error_logs', 'error_summary')) {
            // NOTE: rows longer than 100 chars will be truncated on rollback.
            DB::statement('ALTER TABLE `error_logs` MODIFY `error_summary` VARCHAR(100) NULL');
        }
    }
};
