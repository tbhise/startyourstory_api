<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin activity (audit) log. Records only IMPORTANT administrative WRITE actions
 * (approvals, rejections, status/permission/money changes, content publish, etc.)
 * — never page views, list/read or navigation. Append-only by design: there is no
 * update/delete API. The table is expected to grow indefinitely, hence the
 * targeted indexes for the admin filter UI (admin, action, entity, date range).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            // admins live in admin_users (no FK by project convention).
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('admin_name', 255)->nullable();
            $table->string('action_type', 64);   // e.g. firm_approved, payment_approved
            $table->string('entity_type', 64);   // e.g. firm, premium_request, blog
            $table->string('entity_id', 64)->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('admin_id', 'idx_aal_admin');
            $table->index('action_type', 'idx_aal_action');
            $table->index('entity_type', 'idx_aal_entity_type');
            $table->index('created_at', 'idx_aal_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};
