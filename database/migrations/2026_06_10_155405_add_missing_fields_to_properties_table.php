<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend type enum to include all frontend-defined property types
        DB::statement("ALTER TABLE properties MODIFY COLUMN type ENUM(
            'apartment','house','studio','villa','commercial','land',
            'chambre_simple','appartement','maison','mini_cite',
            'local_commercial','chambre_etudiante','logement_meuble'
        ) NOT NULL");

        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedInteger('deposit_amount')->nullable()->after('price');
            $table->unsignedTinyInteger('min_rental_months')->nullable()->after('deposit_amount');
            $table->unsignedSmallInteger('floor')->nullable()->after('bathrooms');
            $table->boolean('accepts_animals')->default(false)->after('amenities');
            $table->boolean('accepts_smokers')->default(false)->after('accepts_animals');
            $table->boolean('accepts_students')->default(true)->after('accepts_smokers');
            $table->json('charges_included')->nullable()->after('accepts_students');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_amount', 'min_rental_months', 'floor',
                'accepts_animals', 'accepts_smokers', 'accepts_students', 'charges_included',
            ]);
        });

        DB::statement("ALTER TABLE properties MODIFY COLUMN type ENUM(
            'apartment','house','studio','villa','commercial','land'
        ) NOT NULL");
    }
};
