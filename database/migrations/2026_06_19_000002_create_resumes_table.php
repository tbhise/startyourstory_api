<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resume Builder drafts. One draft row per user (unique user_id) holding the
     * selected template and the full resume content as JSON. Guarded so it is
     * safe on every environment.
     */
    public function up(): void
    {
        if (Schema::hasTable('resumes')) {
            return;
        }

        Schema::create('resumes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('template_key', 50)->default('classic_professional');
            $table->json('resume_data')->nullable();
            $table->timestamps();

            $table->unique('user_id', 'uq_resumes_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumes');
    }
};
