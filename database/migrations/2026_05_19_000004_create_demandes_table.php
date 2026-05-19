<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('email');
            $table->string('telephone', 20)->nullable();
            $table->text('message');
            $table->foreignId('bien_id')->nullable()->constrained('biens')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('statut', ['en_attente', 'en_cours', 'traitee'])->default('en_attente');
            $table->timestamps();

            $table->index(['statut', 'bien_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
