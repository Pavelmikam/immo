<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('action', 100);

            $table->nullableMorphs('loggable');

            $table->json('before')->nullable();
            $table->json('after')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            $table->index(['admin_id', 'created_at'],      'admin_logs_admin_created');
            $table->index(['loggable_type', 'loggable_id'], 'admin_logs_loggable');
            $table->index('action',                         'admin_logs_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
    }
};
