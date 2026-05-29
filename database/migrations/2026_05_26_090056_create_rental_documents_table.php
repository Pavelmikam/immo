<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rental_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            $table->enum('type', [
                'cni',
                'passeport',
                'certificat_residence',
                'bulletin_salaire',
                'attestation_travail',
                'releve_bancaire',
                'garant_cni',
                'garant_salaire',
                'autre',
            ]);

            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 50);
            $table->unsignedInteger('file_size');

            $table->string('description', 255)->nullable();

            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['rental_request_id', 'type'], 'rd_request_type');
            $table->index('is_verified',                 'rd_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_documents');
    }
};
