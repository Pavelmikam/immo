<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neighborhood_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->enum('criterion', [
                'eau', 'electricite', 'securite', 'transport',
                'commerces', 'routes', 'sante', 'education',
            ]);

            $table->tinyInteger('score')->unsigned();

            $table->decimal('latitude',  10, 7);
            $table->decimal('longitude', 10, 7);

            $table->string('city', 100)->nullable();
            $table->string('neighborhood', 100)->nullable();

            $table->text('comment')->nullable();

            $table->boolean('is_validated')->default(true);
            $table->boolean('is_flagged')->default(false);

            $table->timestamps();

            $table->index(['criterion', 'latitude', 'longitude'], 'nr_criterion_geo');
            $table->index(['user_id', 'criterion'],               'nr_user_criterion');
            $table->index('city',                                  'nr_city');
            $table->index('is_validated',                          'nr_validated');
            $table->index('created_at',                            'nr_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neighborhood_reports');
    }
};
