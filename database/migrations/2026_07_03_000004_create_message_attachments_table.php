<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            // Denormalised so the download endpoint can authorise with one query.
            $table->unsignedBigInteger('conversation_id');
            $table->enum('type', ['image', 'pdf']);
            // Private-disk relative path — never serialised to any client.
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            // Images only — lets the chat UI reserve space before load.
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('message_id', 'idx_ma_message');
            $table->index('conversation_id', 'idx_ma_conversation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
