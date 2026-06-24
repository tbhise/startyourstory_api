<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            // CTA click tracking for re-engagement (and any future) campaigns.
            // click_count: total redirect hits. clicked_at: first click only.
            $table->integer('click_count')->default(0)->after('sent_at');
            $table->timestamp('clicked_at')->nullable()->after('click_count');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn(['click_count', 'clicked_at']);
        });
    }
};
