<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Interview reminder tracking — prevents duplicate reminder sends
            $table->timestamp('reminder_24h_sent_at')->nullable()->after('interview_responded_at');
            $table->timestamp('reminder_1h_sent_at')->nullable()->after('reminder_24h_sent_at');

            // Application digest tracking — prevents an application from
            // appearing in more than one digest email
            $table->timestamp('digest_notified_at')->nullable()->after('reminder_1h_sent_at');

            $table->index('reminder_24h_sent_at');
            $table->index('reminder_1h_sent_at');
            $table->index('digest_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['reminder_24h_sent_at']);
            $table->dropIndex(['reminder_1h_sent_at']);
            $table->dropIndex(['digest_notified_at']);
            $table->dropColumn(['reminder_24h_sent_at', 'reminder_1h_sent_at', 'digest_notified_at']);
        });
    }
};
