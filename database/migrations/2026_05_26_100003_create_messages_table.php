<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('sender_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->text('body');

            $table->enum('type', ['text', 'system'])->default('text');

            $table->timestamps();

            $table->index(['conversation_id', 'created_at'], 'msg_conv_created');
            $table->index('sender_id',                        'msg_sender');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
