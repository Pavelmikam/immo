<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'body')) {
                $table->text('body')->nullable()->after('sender_id');
            }

            if (!Schema::hasColumn('messages', 'type')) {
                $table->enum('type', ['text', 'system'])->default('text')->after('body');
            }

            // Colonne legacy française — la rendre nullable pour ne pas bloquer les INSERT
            if (Schema::hasColumn('messages', 'contenu')) {
                $table->text('contenu')->nullable()->change();
            }
        });

        // Index si absent
        $existingIndexes = array_column(Schema::getIndexes('messages'), 'name');
        if (!in_array('msg_conv_created', $existingIndexes)) {
            Schema::table('messages', function (Blueprint $table) {
                $table->index(['conversation_id', 'created_at'], 'msg_conv_created');
            });
        }
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'body')) {
                $table->dropColumn('body');
            }
            if (Schema::hasColumn('messages', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('messages', 'contenu')) {
                $table->text('contenu')->nullable(false)->change();
            }
        });
    }
};
