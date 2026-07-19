<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lives in the separate ca_library database (see config/database.php).
// Resource types are shared across subjects (linked via study materials).
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->create('ca_library_resource_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->dropIfExists('ca_library_resource_types');
    }
};
