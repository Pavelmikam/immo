<?php

namespace Tests\Unit\Models;

use App\Models\Property;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeReport(array $attrs = []): Report
    {
        $reporter = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        $owner    = User::factory()->create(['role' => 'proprietaire', 'is_active' => true]);
        $property = Property::factory()->for($owner, 'owner')->active()->create();

        return Report::create(array_merge([
            'reporter_id'     => $reporter->id,
            'reportable_type' => Property::class,
            'reportable_id'   => $property->id,
            'reason'          => 'arnaque_suspectee',
            'status'          => 'en_attente',
        ], $attrs));
    }

    public function test_isPending_retourne_true_si_en_attente(): void
    {
        $report = $this->makeReport(['status' => 'en_attente']);
        $this->assertTrue($report->isPending());
    }

    public function test_isResolved_retourne_true_si_resolu(): void
    {
        $report = $this->makeReport(['status' => 'resolu']);
        $this->assertTrue($report->isResolved());
    }

    public function test_isResolved_retourne_true_si_rejete(): void
    {
        $report = $this->makeReport(['status' => 'rejete']);
        $this->assertTrue($report->isResolved());
    }

    public function test_scope_pending_retourne_seulement_en_attente(): void
    {
        $this->makeReport(['status' => 'en_attente']);
        $this->makeReport(['status' => 'en_attente']);
        $this->makeReport(['status' => 'resolu']);

        $this->assertCount(2, Report::pending()->get());
    }

    public function test_scope_byStatus_filtre_correctement(): void
    {
        $this->makeReport(['status' => 'en_cours']);
        $this->makeReport(['status' => 'en_cours']);
        $this->makeReport(['status' => 'en_attente']);

        $this->assertCount(2, Report::byStatus('en_cours')->get());
    }

    public function test_relation_reportable_polymorphique_fonctionne(): void
    {
        $owner    = User::factory()->create(['role' => 'proprietaire', 'is_active' => true]);
        $property = Property::factory()->for($owner, 'owner')->active()->create();
        $reporter = User::factory()->create(['role' => 'locataire', 'is_active' => true]);

        $report = Report::create([
            'reporter_id'     => $reporter->id,
            'reportable_type' => Property::class,
            'reportable_id'   => $property->id,
            'reason'          => 'arnaque_suspectee',
        ]);

        $this->assertInstanceOf(Property::class, $report->reportable);
        $this->assertEquals($property->id, $report->reportable->id);
    }
}
