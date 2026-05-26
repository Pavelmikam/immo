<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villes', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('code_postal', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->timestamps();

            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villes');
    }
};
