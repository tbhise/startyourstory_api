<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('error_logs', 'error_summary')) {
            Schema::table('error_logs', function (Blueprint $table) {
                // Short, safe, human-readable one-line summary of a backend
                // exception. Full debugging detail stays in storage/logs/laravel.log.
                $table->string('error_summary', 100)->nullable()->after('message');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('error_logs', 'error_summary')) {
            Schema::table('error_logs', function (Blueprint $table) {
                $table->dropColumn('error_summary');
            });
        }
    }
};
