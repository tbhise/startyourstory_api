<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ca_library database. Records when the student accepted the CA Library
// Terms & Conditions for this submission — enforced server-side in
// CaTestSubmissionController@store (required|accepted), not just a UI checkbox.
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->table('ca_test_submissions', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->table('ca_test_submissions', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });
    }
};
