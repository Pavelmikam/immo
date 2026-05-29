<?php

namespace Tests\Unit\Services;

use App\Contracts\DocumentServiceInterface;
use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalRequestService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;
use Tests\Traits\CreatesRentalRequests;

class RentalRequestServiceTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesRentalRequests;

    private RentalRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $docService    = Mockery::mock(DocumentServiceInterface::class);
        $this->service = new RentalRequestService($docService);
    }

    public function test_createRequest_cree_demande_et_incremente_compteur(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();

        $request = $this->service->createRequest($tenant, $property, ['message' => 'Je suis intéressé par ce bien.']);

        $this->assertInstanceOf(RentalRequest::class, $request);
        $this->assertEquals('en_attente', $request->status);
        $this->assertEquals(1, $property->fresh()->requests_count);
    }

    public function test_createRequest_leve_exception_si_bien_non_actif(): void
    {
        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->pending()->create();
        $tenant   = $this->makeTenant();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ce bien n\'est pas disponible à la location.');

        $this->service->createRequest($tenant, $property, ['message' => 'Message.']);
    }

    public function test_createRequest_leve_exception_si_propre_bien(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $owner->update(['role' => 'locataire']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas postuler sur votre propre bien.');

        $this->service->createRequest($owner->fresh(), $property, ['message' => 'Message.']);
    }

    public function test_createRequest_leve_exception_si_demande_active_existante(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();

        $this->createRentalRequest($tenant, $property);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Vous avez déjà une demande en cours pour ce bien.');

        $this->service->createRequest($tenant, $property, ['message' => 'Message.']);
    }

    public function test_acceptRequest_met_a_jour_statut_et_bien(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->service->acceptRequest($request, $owner, 'Bienvenue !');

        $this->assertEquals('acceptee', $request->fresh()->status);
        $this->assertEquals('sous_reservation', $property->fresh()->status);
    }

    public function test_acceptRequest_refuse_autres_demandes_en_attente(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $req1     = $this->createRentalRequest(null, $property);
        $req2     = $this->createRentalRequest(null, $property);

        $this->service->acceptRequest($req1, $owner, 'Bienvenue !');

        $this->assertEquals('refusee', $req2->fresh()->status);
    }

    public function test_acceptRequest_leve_exception_si_non_proprietaire(): void
    {
        $owner    = $this->makeProprietaire();
        $other    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->expectException(DomainException::class);

        $this->service->acceptRequest($request, $other, 'Bienvenue !');
    }

    public function test_refuseRequest_met_a_jour_statut(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->service->refuseRequest($request, $owner, 'Profil non retenu.');

        $this->assertEquals('refusee', $request->fresh()->status);
        $this->assertEquals('Profil non retenu.', $request->fresh()->owner_response);
    }

    public function test_cancelRequest_met_a_jour_statut(): void
    {
        $tenant  = $this->makeTenant();
        $request = $this->createRentalRequest($tenant);

        $this->service->cancelRequest($request, $tenant);

        $this->assertEquals('annulee', $request->fresh()->status);
    }

    public function test_cancelRequest_leve_exception_si_non_locataire(): void
    {
        $tenant  = $this->makeTenant();
        $other   = $this->makeTenant();
        $request = $this->createRentalRequest($tenant);

        $this->expectException(DomainException::class);

        $this->service->cancelRequest($request, $other);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
