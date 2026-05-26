<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'property_id'], 'favorites_user_property_unique');
            $table->index('user_id', 'favorites_user_idx');
            $table->index('property_id', 'favorites_property_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
