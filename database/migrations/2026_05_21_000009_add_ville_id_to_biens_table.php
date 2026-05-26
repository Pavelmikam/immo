<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biens', function (Blueprint $table) {
            $table->foreignId('ville_id')
                ->nullable()
                ->after('code_postal')
                ->constrained('villes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('biens', function (Blueprint $table) {
            $table->dropForeign(['ville_id']);
            $table->dropColumn('ville_id');
        });
    }
};
