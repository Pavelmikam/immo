<?php

use Illuminate\Database\Migrations\Migration;

// Role enum (locataire/proprietaire/admin) is now defined in the base users migration.
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
