<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Rich client-error diagnostics (2026-07-13).
     *
     * Frequently-filtered fields get dedicated (indexed) columns; everything
     * else (network state, payload/file details, timeline, connectivity probe,
     * session context, raw axios error) lives in the `diagnostics` JSON blob.
     * All columns are nullable so existing rows and old clients stay valid.
     *
     *   request_id  — X-Request-ID correlating browser ↔ Laravel ↔ nginx logs
     *   category    — classified cause ("Request Timeout", "Offline", …)
     *   error_code  — raw axios code (ERR_NETWORK, ECONNABORTED, …)
     *   method      — HTTP method of the failed request
     *   endpoint    — normalized API path (no query string)
     *   page        — SPA route the user was on
     *   duration_ms — measured request duration
     *   browser/os/device — parsed from the UA client-side
     *   diagnostics — full structured JSON (sanitized server-side)
     */
    public function up(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('error_logs', 'request_id')) {
                $table->string('request_id', 64)->nullable()->after('source');
            }
            if (! Schema::hasColumn('error_logs', 'category')) {
                $table->string('category', 40)->nullable()->after('request_id');
            }
            if (! Schema::hasColumn('error_logs', 'error_code')) {
                $table->string('error_code', 40)->nullable()->after('category');
            }
            if (! Schema::hasColumn('error_logs', 'method')) {
                $table->string('method', 10)->nullable()->after('error_code');
            }
            if (! Schema::hasColumn('error_logs', 'endpoint')) {
                $table->string('endpoint', 255)->nullable()->after('method');
            }
            if (! Schema::hasColumn('error_logs', 'page')) {
                $table->string('page', 255)->nullable()->after('endpoint');
            }
            if (! Schema::hasColumn('error_logs', 'duration_ms')) {
                $table->unsignedInteger('duration_ms')->nullable()->after('page');
            }
            if (! Schema::hasColumn('error_logs', 'browser')) {
                $table->string('browser', 40)->nullable()->after('duration_ms');
            }
            if (! Schema::hasColumn('error_logs', 'os')) {
                $table->string('os', 40)->nullable()->after('browser');
            }
            if (! Schema::hasColumn('error_logs', 'device')) {
                $table->string('device', 20)->nullable()->after('os');
            }
            if (! Schema::hasColumn('error_logs', 'diagnostics')) {
                $table->json('diagnostics')->nullable()->after('stack');
            }
        });

        Schema::table('error_logs', function (Blueprint $table) {
            $table->index(['category', 'created_at'], 'idx_error_category_created');
            $table->index('endpoint', 'idx_error_endpoint');
            $table->index('request_id', 'idx_error_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropIndex('idx_error_category_created');
            $table->dropIndex('idx_error_endpoint');
            $table->dropIndex('idx_error_request_id');
        });

        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropColumn([
                'request_id', 'category', 'error_code', 'method', 'endpoint',
                'page', 'duration_ms', 'browser', 'os', 'device', 'diagnostics',
            ]);
        });
    }
};
