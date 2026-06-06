<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->morphs('reportable');

            $table->enum('reason', [
                'contenu_inapproprie',
                'arnaque_suspectee',
                'informations_fausses',
                'photos_trompeuses',
                'prix_abusif',
                'annonce_inexistante',
                'comportement_abusif',
                'autre',
            ]);

            $table->text('description')->nullable();

            $table->enum('status', [
                'en_attente',
                'en_cours',
                'resolu',
                'rejete',
            ])->default('en_attente');

            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('handled_at')->nullable();

            $table->timestamps();

            $table->unique(['reporter_id', 'reportable_type', 'reportable_id'],
                           'reports_reporter_reportable_unique');
            $table->index(['reportable_type', 'reportable_id'], 'reports_reportable_idx');
            $table->index('status',                             'reports_status_idx');
            $table->index('reporter_id',                        'reports_reporter_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
