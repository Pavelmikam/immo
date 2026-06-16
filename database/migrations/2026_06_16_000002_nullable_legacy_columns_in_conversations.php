<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Ces colonnes existent dans l'ancienne version du schéma (noms français).
            // Elles ne sont plus utilisées par le code mais MySQL les exige à l'INSERT.
            // On les rend nullable pour ne pas bloquer les nouvelles insertions.
            if (Schema::hasColumn('conversations', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->change();
            }
            if (Schema::hasColumn('conversations', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('conversations', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable(false)->change();
            }
        });
    }
};
