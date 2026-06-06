<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributor_badges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->enum('badge', [
                'premier_signalement',
                'contributeur_actif',
                'expert_quartier',
                'explorateur',
                'fiable',
            ]);

            $table->timestamp('awarded_at');

            $table->unique(['user_id', 'badge'], 'cb_user_badge_unique');
            $table->index('user_id',             'cb_user_idx');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributor_badges');
    }
};
