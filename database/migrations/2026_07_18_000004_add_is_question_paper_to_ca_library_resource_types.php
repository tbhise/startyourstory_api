<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ca_library database. Flags resource types that represent question papers —
// future features must use this flag, never hardcoded type names.
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->table('ca_library_resource_types', function (Blueprint $table) {
            $table->boolean('is_question_paper')->default(false)->after('name');
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->table('ca_library_resource_types', function (Blueprint $table) {
            $table->dropColumn('is_question_paper');
        });
    }
};
