<?php

namespace Tests\Feature\Report;

use App\Models\Message;
use App\Models\Property;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesConversations;
use Tests\Traits\CreatesProperties;

class UserReportTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesConversations;

    private function makeTenant(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $attrs));
    }

    public function test_utilisateur_peut_signaler_une_annonce(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $response = $this->withToken($token)->postJson("/api/reports/properties/{$property->id}", [
            'reason' => 'arnaque_suspectee',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reports', [
            'reporter_id'     => $tenant->id,
            'reportable_type' => Property::class,
            'reportable_id'   => $property->id,
        ]);
    }

    public function test_impossible_signaler_sa_propre_annonce(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->postJson("/api/reports/properties/{$property->id}", [
            'reason' => 'arnaque_suspectee',
        ])->assertStatus(422);
    }

    public function test_impossible_signaler_deux_fois_la_meme_annonce(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)->postJson("/api/reports/properties/{$property->id}", [
            'reason' => 'arnaque_suspectee',
        ])->assertStatus(201);

        $this->withToken($token)->postJson("/api/reports/properties/{$property->id}", [
            'reason' => 'arnaque_suspectee',
        ])->assertStatus(422);
    }

    public function test_utilisateur_peut_signaler_un_message(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $conv     = $this->createConversation($tenant, $property);
        $message  = Message::factory()->create([
            'conversation_id' => $conv->id,
            'sender_id'       => $owner->id,
        ]);
        $token = $this->tokenFor($tenant);

        $response = $this->withToken($token)->postJson("/api/reports/messages/{$message->id}", [
            'reason' => 'comportement_abusif',
        ]);

        $response->assertStatus(201);
    }

    public function test_impossible_signaler_son_propre_message(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $conv     = $this->createConversation($tenant, $property);
        $message  = Message::factory()->create([
            'conversation_id' => $conv->id,
            'sender_id'       => $tenant->id,
        ]);
        $token = $this->tokenFor($tenant);

        $this->withToken($token)->postJson("/api/reports/messages/{$message->id}", [
            'reason' => 'comportement_abusif',
        ])->assertStatus(422);
    }

    public function test_non_participant_ne_peut_pas_signaler_message(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $conv     = $this->createConversation($tenant, $property);
        $message  = Message::factory()->create([
            'conversation_id' => $conv->id,
            'sender_id'       => $owner->id,
        ]);
        $tiers = $this->makeTenant();
        $token = $this->tokenFor($tiers);

        $this->withToken($token)->postJson("/api/reports/messages/{$message->id}", [
            'reason' => 'comportement_abusif',
        ])->assertStatus(403);
    }

    public function test_raison_invalide_refusee(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)->postJson("/api/reports/properties/{$property->id}", [
            'reason' => 'mauvaise_raison',
        ])->assertStatus(422)->assertJsonValidationErrors(['reason']);
    }

    public function test_non_authentifie_ne_peut_pas_signaler(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);

        $this->postJson("/api/reports/properties/{$property->id}", [
            'reason' => 'arnaque_suspectee',
        ])->assertStatus(401);
    }
}
