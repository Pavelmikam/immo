<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neighborhood_scores', function (Blueprint $table) {
            $table->id();

            $table->string('city', 100);
            $table->string('neighborhood', 100)->nullable();

            $table->decimal('center_latitude',  10, 7);
            $table->decimal('center_longitude', 10, 7);

            $table->enum('criterion', [
                'eau', 'electricite', 'securite', 'transport',
                'commerces', 'routes', 'sante', 'education',
            ]);

            $table->decimal('average_score', 3, 2);
            $table->decimal('global_score',  3, 2)->nullable();

            $table->unsignedInteger('report_count');
            $table->unsignedInteger('unique_reporters');

            $table->date('period_start');
            $table->date('period_end');

            $table->timestamp('computed_at');

            $table->timestamps();

            $table->unique(
                ['city', 'neighborhood', 'criterion'],
                'ns_city_neighborhood_criterion_unique'
            );
            $table->index(['city', 'neighborhood'], 'ns_city_neighborhood');
            $table->index('criterion',              'ns_criterion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neighborhood_scores');
    }
};
