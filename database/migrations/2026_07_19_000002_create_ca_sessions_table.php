<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ca_library database. CA Library auth sessions — mirrors the SYS
// user_sessions pattern (token cookie → session row) but with its own
// `ca_auth_token` cookie so SYS and CA Library logins never interfere.
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->create('ca_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('student_id', 'idx_cas_student');
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->dropIfExists('ca_sessions');
    }
};
