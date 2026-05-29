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
            $table->foreignId('message_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 50);
            $table->unsignedInteger('file_size');

            $table->enum('attachment_type', ['image', 'document'])->default('document');

            $table->timestamps();

            $table->index('message_id', 'ma_message_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
