<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conversations')) {
            return;
        }

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('initiated_by')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('rental_request_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->string('subject', 200)->nullable();
            $table->text('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->foreignId('last_message_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->boolean('is_archived')->default(false);

            $table->timestamps();

            $table->index(['property_id', 'initiated_by'], 'conv_property_user');
            $table->index('last_message_at',               'conv_last_message');
            $table->index('rental_request_id',             'conv_rental_request');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
