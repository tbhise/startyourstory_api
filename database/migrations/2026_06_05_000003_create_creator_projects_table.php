<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('category', 100);
            $table->enum('budget_type', ['fixed', 'range', 'negotiable'])->default('fixed');
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->unsignedSmallInteger('delivery_days')->nullable();
            $table->json('skills_required')->nullable();
            $table->json('attachments')->nullable();
            $table->enum('status', ['draft', 'published', 'closed', 'cancelled'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('firm_id')->references('id')->on('firm_profiles')->onDelete('cascade');
            $table->index(['firm_id', 'status']);
            $table->index(['status', 'category']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_projects');
    }
};
