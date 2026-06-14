<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            // string | integer | decimal | boolean
            $table->string('setting_type', 20)->default('string');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('category', 50)->default('general');
            $table->boolean('is_editable')->default(true);
            $table->timestamps();

            $table->index('category', 'idx_system_settings_category');
        });

        // Audit log for every setting change (no existing activity-log infra).
        Schema::create('system_setting_audits', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->string('admin_name', 255)->nullable();
            $table->timestamps();

            $table->index('setting_key', 'idx_setting_audits_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_setting_audits');
        Schema::dropIfExists('system_settings');
    }
};
