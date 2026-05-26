<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('original_path');
            $table->string('optimized_path');
            $table->string('thumbnail_path');
            $table->unsignedTinyInteger('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->string('caption', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['property_id', 'order']);
            $table->index(['property_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};
