<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing contacts — standalone repository of firms that are NOT registered
 * on Start Your Story, used only as the audience for Artisan-triggered
 * marketing sends (`marketing:import`, `marketing:send`).
 *
 * Deliberately unrelated to `users` / `firm_profiles`: no foreign key, no
 * account, no login. `email` is unique so re-running an import is idempotent.
 * `last_emailed_at` is stamped when an email is queued for the contact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('firm_name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_emailed_at')->nullable();
            $table->timestamps();

            // The send audience is always `status = 'active'`.
            $table->index('status', 'idx_marketing_contacts_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_contacts');
    }
};
