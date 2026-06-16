<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'property_id')) {
                $table->foreignId('property_id')
                      ->after('id')
                      ->constrained()
                      ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'initiated_by')) {
                $table->foreignId('initiated_by')
                      ->after('property_id')
                      ->constrained('users')
                      ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'rental_request_id')) {
                $table->foreignId('rental_request_id')
                      ->nullable()
                      ->after('initiated_by')
                      ->constrained()
                      ->nullOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'subject')) {
                $table->string('subject', 200)->nullable()->after('rental_request_id');
            }

            if (!Schema::hasColumn('conversations', 'last_message_preview')) {
                $table->text('last_message_preview')->nullable()->after('subject');
            }

            if (!Schema::hasColumn('conversations', 'last_message_at')) {
                $table->timestamp('last_message_at')->nullable()->after('last_message_preview');
            }

            if (!Schema::hasColumn('conversations', 'last_message_by')) {
                $table->foreignId('last_message_by')
                      ->nullable()
                      ->after('last_message_at')
                      ->constrained('users')
                      ->nullOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('last_message_by');
            }
        });

        // Ajouter l'index composite s'il n'existe pas
        $existingIndexes = array_column(Schema::getIndexes('conversations'), 'name');
        if (!in_array('conv_property_user', $existingIndexes)) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->index(['property_id', 'initiated_by'], 'conv_property_user');
            });
        }
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            foreach (['property_id', 'initiated_by', 'rental_request_id', 'last_message_by'] as $col) {
                if (Schema::hasColumn('conversations', $col)) {
                    $table->dropForeign(['conversations_' . $col . '_foreign']);
                    $table->dropColumn($col);
                }
            }
            foreach (['subject', 'last_message_preview', 'last_message_at', 'is_archived'] as $col) {
                if (Schema::hasColumn('conversations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
