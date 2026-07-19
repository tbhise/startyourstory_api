<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ca_library database. CA Library students are fully independent of the main
// SYS users / student_profiles tables — no cross-database link by design.
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->create('ca_students', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            // NULL until the student optionally sets one (first download is
            // email-verification only, no password required).
            $table->string('password', 255)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('status', 20)->default('active');
            // Email-verification link: only the sha256 hash is stored.
            $table->string('verify_token_hash', 64)->nullable();
            $table->timestamp('verify_token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->dropIfExists('ca_students');
    }
};
