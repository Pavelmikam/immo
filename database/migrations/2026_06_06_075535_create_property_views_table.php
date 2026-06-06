<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->string('session_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer', 500)->nullable();

            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->index(['property_id', 'viewed_at'],  'pv_property_date');
            $table->index(['property_id', 'user_id'],    'pv_property_user');
            $table->index(['property_id', 'session_id'], 'pv_property_session');
            $table->index('viewed_at',                   'pv_date');
            $table->index('user_id',                     'pv_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_views');
    }
};
