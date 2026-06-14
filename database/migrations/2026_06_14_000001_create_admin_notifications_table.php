<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            // Category of notification — kept as a free string (with app-level
            // constants) so new types can be added without a schema change.
            $table->string('type', 50)->index();
            $table->string('title', 255);
            $table->text('message');
            // Where the admin should be taken when acting on it (relative path).
            $table->string('action_url', 500)->nullable();
            // Arbitrary structured context (ids, amounts, names…) for future use.
            $table->json('metadata')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            // Hot path: unread list ordered by recency.
            $table->index(['is_read', 'created_at'], 'idx_admin_notifications_unread');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
