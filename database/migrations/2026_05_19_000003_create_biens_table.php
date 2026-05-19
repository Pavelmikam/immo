<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biens', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description');
            $table->decimal('prix', 12, 2);
            $table->decimal('surface', 8, 2);
            $table->unsignedSmallInteger('nb_pieces');
            $table->unsignedSmallInteger('nb_chambres')->nullable();
            $table->unsignedSmallInteger('nb_salles_bain')->nullable();
            $table->string('adresse');
            $table->string('ville', 100);
            $table->string('code_postal', 10)->nullable();
            $table->enum('statut', ['vente', 'location'])->default('vente');
            $table->boolean('disponible')->default(true);
            $table->foreignId('type_bien_id')->constrained('type_biens')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['ville', 'statut', 'disponible']);
            $table->index('type_bien_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biens');
    }
};
