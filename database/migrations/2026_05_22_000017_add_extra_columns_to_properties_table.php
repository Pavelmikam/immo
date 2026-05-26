<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->json('amenities')->nullable()->after('is_featured');
            $table->date('available_from')->nullable()->after('amenities');
            $table->unsignedInteger('views_count')->default(0)->after('available_from');
            $table->unsignedInteger('favorites_count')->default(0)->after('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['amenities', 'available_from', 'views_count', 'favorites_count']);
        });
    }
};
