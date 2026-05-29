<?php

namespace Tests\Unit\Models;

use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;
use Tests\Traits\CreatesRentalRequests;

class RentalRequestModelTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesRentalRequests;

    public function test_isPending_retourne_vrai_si_en_attente(): void
    {
        $request = $this->createRentalRequest(null, null, ['status' => 'en_attente']);
        $this->assertTrue($request->isPending());
    }

    public function test_isPending_retourne_faux_si_acceptee(): void
    {
        $property = $this->createApprovedProperty();
        $request  = $this->createRentalRequest(null, $property, ['status' => 'acceptee']);
        $this->assertFalse($request->isPending());
    }

    public function test_isAccepted_retourne_vrai_si_acceptee(): void
    {
        $property = $this->createApprovedProperty();
        $request  = $this->createRentalRequest(null, $property, ['status' => 'acceptee']);
        $this->assertTrue($request->isAccepted());
    }

    public function test_canBeCancelledBy_vrai_si_tenant_et_en_attente(): void
    {
        $tenant  = $this->makeTenant();
        $request = $this->createRentalRequest($tenant);

        $this->assertTrue($request->canBeCancelledBy($tenant));
    }

    public function test_canBeCancelledBy_faux_si_autre_utilisateur(): void
    {
        $tenant  = $this->makeTenant();
        $other   = $this->makeTenant();
        $request = $this->createRentalRequest($tenant);

        $this->assertFalse($request->canBeCancelledBy($other));
    }

    public function test_canBeCancelledBy_faux_si_non_en_attente(): void
    {
        $tenant   = $this->makeTenant();
        $property = $this->createApprovedProperty();
        $request  = $this->createRentalRequest($tenant, $property, ['status' => 'acceptee']);

        $this->assertFalse($request->canBeCancelledBy($tenant));
    }

    public function test_canBeDecidedBy_vrai_si_proprietaire_et_en_attente(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->assertTrue($request->canBeDecidedBy($owner));
    }

    public function test_canBeDecidedBy_faux_si_autre_proprietaire(): void
    {
        $owner    = $this->makeProprietaire();
        $other    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->assertFalse($request->canBeDecidedBy($other));
    }

    public function test_scope_pending_retourne_uniquement_en_attente(): void
    {
        $property = $this->createApprovedProperty();
        $this->createRentalRequest(null, $property, ['status' => 'en_attente']);
        $this->createRentalRequest(null, $property, ['status' => 'acceptee']);
        $this->createRentalRequest(null, $property, ['status' => 'refusee']);

        $pending = RentalRequest::pending()->get();

        $this->assertCount(1, $pending);
        $this->assertEquals('en_attente', $pending->first()->status);
    }

    public function test_scope_forTenant_filtre_par_locataire(): void
    {
        $tenant = $this->makeTenant();
        $other  = $this->makeTenant();

        $this->createRentalRequest($tenant);
        $this->createRentalRequest($tenant);
        $this->createRentalRequest($other);

        $results = RentalRequest::forTenant($tenant->id)->get();

        $this->assertCount(2, $results);
    }

    public function test_scope_forProperty_filtre_par_bien(): void
    {
        $property1 = $this->createApprovedProperty();
        $property2 = $this->createApprovedProperty();

        $this->createRentalRequest(null, $property1);
        $this->createRentalRequest(null, $property1);
        $this->createRentalRequest(null, $property2);

        $results = RentalRequest::forProperty($property1->id)->get();

        $this->assertCount(2, $results);
    }

    public function test_relation_property_chargee_correctement(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->assertEquals($property->id, $request->property->id);
    }

    public function test_relation_tenant_chargee_correctement(): void
    {
        $tenant  = $this->makeTenant();
        $request = $this->createRentalRequest($tenant);

        $this->assertEquals($tenant->id, $request->tenant->id);
    }
}
