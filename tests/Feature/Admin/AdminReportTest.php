<?php

namespace Tests\Feature\Admin;

use App\Models\Message;
use App\Models\Property;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesConversations;
use Tests\Traits\CreatesProperties;

class AdminReportTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesConversations;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role'              => 'admin',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
    }

    private function makeReport(array $attrs = []): Report
    {
        $reporter = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        $property = $this->createApprovedProperty();

        return Report::create(array_merge([
            'reporter_id'     => $reporter->id,
            'reportable_type' => Property::class,
            'reportable_id'   => $property->id,
            'reason'          => 'arnaque_suspectee',
            'status'          => 'en_attente',
        ], $attrs));
    }

    public function test_admin_peut_lister_les_signalements(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->makeReport();
        $this->makeReport();
        $this->makeReport();

        $response = $this->withToken($token)->getJson('/api/admin/reports');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_filtre_par_status_fonctionne(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->makeReport(['status' => 'en_attente']);
        $this->makeReport(['status' => 'en_attente']);
        $this->makeReport(['status' => 'resolu', 'handled_at' => now(), 'handled_by' => $admin->id]);

        $response = $this->withToken($token)->getJson('/api/admin/reports?status=en_attente');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filtre_par_type_property(): void
    {
        $admin      = $this->makeAdmin();
        $token      = $this->tokenFor($admin);
        $reporter   = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        $owner      = $this->makeProprietaire();
        $property   = $this->createApprovedProperty($owner);

        // Signalement sur Property
        Report::create([
            'reporter_id'     => $reporter->id,
            'reportable_type' => Property::class,
            'reportable_id'   => $property->id,
            'reason'          => 'arnaque_suspectee',
        ]);

        // Signalement sur Message
        $tenant = User::factory()->create(['role' => 'locataire', 'is_active' => true, 'email_verified_at' => now()]);
        $conv   = $this->createConversation($tenant, $property);
        $msg    = Message::factory()->create(['conversation_id' => $conv->id, 'sender_id' => $owner->id]);
        Report::create([
            'reporter_id'     => $reporter->id,
            'reportable_type' => Message::class,
            'reportable_id'   => $msg->id,
            'reason'          => 'comportement_abusif',
        ]);

        $response = $this->withToken($token)->getJson('/api/admin/reports?type=property');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_peut_resoudre_signalement(): void
    {
        $admin  = $this->makeAdmin();
        $token  = $this->tokenFor($admin);
        $report = $this->makeReport();

        $this->withToken($token)->postJson("/api/admin/reports/{$report->id}/handle", [
            'action'     => 'resolve',
            'admin_note' => 'Annonce supprimée après vérification.',
        ])->assertStatus(200);

        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'resolu']);
        $this->assertDatabaseHas('admin_logs', ['action' => 'report.resolve', 'admin_id' => $admin->id]);
    }

    public function test_admin_peut_rejeter_signalement(): void
    {
        $admin  = $this->makeAdmin();
        $token  = $this->tokenFor($admin);
        $report = $this->makeReport();

        $this->withToken($token)->postJson("/api/admin/reports/{$report->id}/handle", [
            'action'     => 'reject',
            'admin_note' => 'Signalement non fondé.',
        ])->assertStatus(200);

        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'rejete']);
    }

    public function test_handle_signalement_deja_traite_refuse(): void
    {
        $admin  = $this->makeAdmin();
        $token  = $this->tokenFor($admin);
        $report = $this->makeReport(['status' => 'resolu', 'handled_at' => now(), 'handled_by' => $admin->id]);

        $this->withToken($token)->postJson("/api/admin/reports/{$report->id}/handle", [
            'action'     => 'resolve',
            'admin_note' => 'Déjà traité.',
        ])->assertStatus(422);
    }

    public function test_handle_sans_note_refuse(): void
    {
        $admin  = $this->makeAdmin();
        $token  = $this->tokenFor($admin);
        $report = $this->makeReport();

        $this->withToken($token)->postJson("/api/admin/reports/{$report->id}/handle", [
            'action' => 'resolve',
        ])->assertStatus(422)->assertJsonValidationErrors(['admin_note']);
    }

    public function test_non_admin_ne_peut_pas_gerer_signalements(): void
    {
        $tenant = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        $token  = $this->tokenFor($tenant);
        $report = $this->makeReport();

        $this->withToken($token)->postJson("/api/admin/reports/{$report->id}/handle", [
            'action'     => 'resolve',
            'admin_note' => 'Test.',
        ])->assertStatus(403);
    }
}
