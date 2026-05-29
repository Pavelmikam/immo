<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', [
                'en_attente',
                'acceptee',
                'refusee',
                'annulee',
                'terminee',
            ])->default('en_attente');

            $table->text('message')->nullable();
            $table->text('owner_response')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamp('visit_scheduled_at')->nullable();
            $table->boolean('visit_confirmed')->default(false);

            $table->boolean('dossier_complete')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'status'], 'rr_property_status');
            $table->index(['tenant_id', 'status'],   'rr_tenant_status');
            $table->index('status',                  'rr_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_requests');
    }
};
