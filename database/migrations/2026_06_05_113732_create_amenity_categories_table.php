<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenity_categories', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['property_type', 'amenity', 'charge']);
            $table->string('value', 100);
            $table->string('label', 150);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['category', 'value'], 'amenity_cat_value_unique');
            $table->index(['category', 'is_active', 'sort_order'], 'amenity_cat_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenity_categories');
    }
};
