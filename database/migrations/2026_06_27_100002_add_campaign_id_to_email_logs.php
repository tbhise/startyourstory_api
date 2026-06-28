<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link campaign sends to their email_logs rows so per-campaign click counts can be
 * rolled up (the click-tracker bumps campaigns.clicked_count on the first click of a
 * campaign-linked log). Nullable + additive: existing transactional logs are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->after('id');
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex(['campaign_id']);
            $table->dropColumn('campaign_id');
        });
    }
};
