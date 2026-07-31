<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaign template support + email open tracking.
 *
 *  - campaigns.subject: the admin's editable subject override for a send. NULL keeps
 *    the template's own default (or, for re-engagement, the per-segment subject).
 *    The chosen template key reuses the existing `campaign_type` column.
 *  - email_logs.open_count / opened_at: mirror of the existing click columns, written
 *    by the signed GET /e/open/{emailLog} pixel. This finally populates the
 *    campaigns.opened_count column that was reserved but never filled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('campaign_name');
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->integer('open_count')->default(0)->after('clicked_at');
            $table->timestamp('opened_at')->nullable()->after('open_count');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('subject');
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn(['open_count', 'opened_at']);
        });
    }
};
