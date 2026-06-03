<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->string('designation')->nullable();
            $table->date('joined_at')->nullable();
            // offer_accepted = offer accepted through portal
            // hired          = marked hired by firm
            // verified       = admin-verified employment
            $table->enum('verification_type', ['offer_accepted', 'hired', 'verified'])
                  ->default('hired');
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
            $table->index('company_id');
            $table->index('user_id');

            $table->foreign('company_id')->references('id')->on('firm_profiles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_employees');
    }
};
