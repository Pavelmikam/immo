<?php

namespace Tests\Feature\RentalRequest;

use App\Models\RentalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;
use Tests\Traits\CreatesRentalRequests;

class RentalDocumentTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesRentalRequests;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
    }

    public function test_locataire_peut_uploader_document(): void
    {
        $tenant  = $this->makeTenant();
        $token   = $this->tokenFor($tenant);
        $request = $this->createRentalRequest($tenant);

        $response = $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/documents",
            [
                'document' => UploadedFile::fake()->create('cni.pdf', 500, 'application/pdf'),
                'type'     => 'cni',
            ]
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('rental_documents', [
            'rental_request_id' => $request->id,
            'type'              => 'cni',
            'uploaded_by'       => $tenant->id,
        ]);
    }

    public function test_upload_remplace_document_meme_type(): void
    {
        $tenant  = $this->makeTenant();
        $token   = $this->tokenFor($tenant);
        $request = $this->createRentalRequest($tenant);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/documents",
            [
                'document' => UploadedFile::fake()->create('cni_v1.pdf', 500, 'application/pdf'),
                'type'     => 'cni',
            ]
        )->assertStatus(201);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/documents",
            [
                'document' => UploadedFile::fake()->create('cni_v2.pdf', 500, 'application/pdf'),
                'type'     => 'cni',
            ]
        )->assertStatus(201);

        $this->assertEquals(1, RentalDocument::where([
            'rental_request_id' => $request->id,
            'type'              => 'cni',
        ])->count());
    }

    public function test_dossier_complet_apres_cni_et_bulletin(): void
    {
        $tenant  = $this->makeTenant();
        $token   = $this->tokenFor($tenant);
        $request = $this->createRentalRequest($tenant);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/documents",
            [
                'document' => UploadedFile::fake()->create('cni.pdf', 500, 'application/pdf'),
                'type'     => 'cni',
            ]
        )->assertStatus(201);

        $this->assertFalse($request->fresh()->dossier_complete);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/documents",
            [
                'document' => UploadedFile::fake()->create('salaire.pdf', 500, 'application/pdf'),
                'type'     => 'bulletin_salaire',
            ]
        )->assertStatus(201);

        $this->assertTrue($request->fresh()->dossier_complete);
    }

    public function test_type_document_invalide_refuse(): void
    {
        $tenant  = $this->makeTenant();
        $token   = $this->tokenFor($tenant);
        $request = $this->createRentalRequest($tenant);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/documents",
            [
                'document' => UploadedFile::fake()->create('doc.pdf', 500, 'application/pdf'),
                'type'     => 'facture_eau',
            ]
        )->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    public function test_fichier_trop_grand_refuse(): void
    {
        $tenant  = $this->makeTenant();
        $token   = $this->tokenFor($tenant);
        $request = $this->createRentalRequest($tenant);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/documents",
            [
                'document' => UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf'),
                'type'     => 'cni',
            ]
        )->assertStatus(422)->assertJsonValidationErrors(['document']);
    }

    public function test_autre_locataire_ne_peut_pas_uploader(): void
    {
        $tenant  = $this->makeTenant();
        $other   = $this->makeTenant();
        $token   = $this->tokenFor($other);
        $request = $this->createRentalRequest($tenant);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/documents",
            [
                'document' => UploadedFile::fake()->create('cni.pdf', 500, 'application/pdf'),
                'type'     => 'cni',
            ]
        )->assertStatus(403);
    }

    public function test_locataire_peut_supprimer_son_document(): void
    {
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);
        $request  = $this->createRentalRequest($tenant);
        $document = RentalDocument::factory()->create([
            'rental_request_id' => $request->id,
            'uploaded_by'       => $tenant->id,
            'type'              => 'cni',
        ]);

        $this->withToken($token)
             ->deleteJson("/api/rental-requests/{$request->id}/documents/{$document->id}")
             ->assertStatus(204);

        $this->assertDatabaseMissing('rental_documents', ['id' => $document->id]);
    }

    public function test_suppression_impossible_si_demande_non_en_attente(): void
    {
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);
        $property = $this->createApprovedProperty();
        $request  = $this->createRentalRequest($tenant, $property, ['status' => 'acceptee']);
        $document = RentalDocument::factory()->create([
            'rental_request_id' => $request->id,
            'uploaded_by'       => $tenant->id,
            'type'              => 'cni',
        ]);

        $this->withToken($token)
             ->deleteJson("/api/rental-requests/{$request->id}/documents/{$document->id}")
             ->assertStatus(403);
    }

    public function test_admin_peut_verifier_document(): void
    {
        $admin    = \App\Models\User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'is_active' => true]);
        $token    = $this->tokenFor($admin);
        $request  = $this->createRentalRequest();
        $document = RentalDocument::factory()->create([
            'rental_request_id' => $request->id,
            'uploaded_by'       => $request->tenant_id,
            'type'              => 'cni',
        ]);

        $this->withToken($token)
             ->postJson("/api/documents/{$document->id}/verify")
             ->assertStatus(200);

        $this->assertDatabaseHas('rental_documents', [
            'id'          => $document->id,
            'is_verified' => true,
        ]);
    }
}
