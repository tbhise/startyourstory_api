<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_project_bids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('creator_id');
            $table->decimal('bid_amount', 12, 2);
            $table->unsignedSmallInteger('delivery_days');
            $table->text('proposal');
            $table->json('portfolio_links')->nullable();
            $table->enum('status', ['pending', 'shortlisted', 'selected', 'rejected', 'withdrawn'])->default('pending');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('creator_projects')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['project_id', 'creator_id'], 'uq_project_creator');
            $table->index(['creator_id', 'status']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_project_bids');
    }
};
