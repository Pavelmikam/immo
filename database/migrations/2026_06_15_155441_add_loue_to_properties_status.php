<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN status ENUM(
            'draft','pending','active','rejected','archived','sous_reservation','loue'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN status ENUM(
            'draft','pending','active','rejected','archived','sous_reservation'
        ) NOT NULL DEFAULT 'draft'");
    }
};
