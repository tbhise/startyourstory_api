<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bell-notification deep links. The `notifications` table (firm/student
     * notification bell) previously stored no destination, so clicking a bell
     * entry could only mark it read — it could not navigate. NotificationHelper
     * already RECEIVES an $actionUrl (for the push mirror) but discarded it for
     * the bell; this column lets us persist that same URL so the new firm
     * Notifications page and the dropdown can route on click.
     *
     * Nullable: legacy rows and notifications with no meaningful destination
     * simply have NULL, and the UI just marks them read without navigating.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('notifications', 'action_url')) {
            Schema::table('notifications', function ($table) {
                $table->string('action_url')->nullable()->after('message');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'action_url')) {
            Schema::table('notifications', function ($table) {
                $table->dropColumn('action_url');
            });
        }
    }
};
