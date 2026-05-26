<?php

use Illuminate\Database\Migrations\Migration;

// Role column is now part of the base users migration (0001_01_01_000000).
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
