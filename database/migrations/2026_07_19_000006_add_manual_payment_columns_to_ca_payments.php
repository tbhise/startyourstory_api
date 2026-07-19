<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ca_library database. Manual payment support: student uploads a payment
// screenshot (private disk) and an admin approves/rejects it. gateway='manual',
// status flow: pending_verification → paid | rejected.
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->table('ca_payments', function (Blueprint $table) {
            $table->string('screenshot_path', 500)->nullable()->after('status');
            $table->string('screenshot_original_name', 255)->nullable()->after('screenshot_path');
            // Reviewing SYS admin_users.id + when (approve or reject).
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('screenshot_original_name');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->table('ca_payments', function (Blueprint $table) {
            $table->dropColumn(['screenshot_path', 'screenshot_original_name', 'reviewed_by', 'reviewed_at']);
        });
    }
};
