<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('bien_id')->nullable()->constrained('biens')->nullOnDelete();
            $table->enum('statut', ['ouverte', 'fermee', 'archivee'])->default('ouverte');
            $table->timestamps();

            $table->unique(['client_id', 'agent_id', 'bien_id']);
            $table->index(['client_id', 'statut']);
            $table->index(['agent_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
