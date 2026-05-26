<?php

namespace Tests\Unit\Services;

use App\Models\Property;
use App\Models\User;
use App\Services\ImageService;
use App\Services\PropertyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class PropertyServiceTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    private PropertyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PropertyService(app(ImageService::class));
    }

    public function test_create_sets_draft_status(): void
    {
        $owner = $this->makeProprietaire();

        $property = $this->service->create($owner, [
            'title'            => 'Appartement à louer à Yaoundé',
            'description'      => str_repeat('Longue description. ', 5),
            'type'             => 'apartment',
            'transaction_type' => 'rent',
            'price'            => 120000,
            'city'             => 'Yaoundé',
        ]);

        $this->assertInstanceOf(Property::class, $property);
        $this->assertEquals('draft', $property->status);
        $this->assertEquals($owner->id, $property->user_id);
    }

    public function test_submit_changes_status_to_pending(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);

        $result = $this->service->submit($property);

        $this->assertEquals('pending', $result->status);
    }

    public function test_approve_changes_status_to_active(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);

        $result = $this->service->approve($property);

        $this->assertEquals('active', $result->status);
        $this->assertNotNull($result->published_at);
    }

    public function test_reject_stores_reason(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);

        $result = $this->service->reject($property, 'Photos insuffisantes.');

        $this->assertEquals('rejected', $result->status);
        $this->assertEquals('Photos insuffisantes.', $result->rejection_reason);
    }

    public function test_archive_changes_status_to_archived(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);

        $result = $this->service->archive($property);

        $this->assertEquals('archived', $result->status);
    }

    public function test_list_returns_only_active_properties(): void
    {
        $owner = $this->makeProprietaire();
        $this->makeActiveProperty($owner);
        $this->makeActiveProperty($owner);
        $this->makeProperty($owner, ['status' => 'pending']);
        $this->makeProperty($owner, ['status' => 'draft']);

        $result = $this->service->list();

        $this->assertEquals(2, $result->total());
    }

    public function test_list_filters_by_city(): void
    {
        $owner = $this->makeProprietaire();
        $this->makeActiveProperty($owner, ['city' => 'Yaoundé']);
        $this->makeActiveProperty($owner, ['city' => 'Douala']);

        $result = $this->service->list(['city' => 'Yaoundé']);

        $this->assertEquals(1, $result->total());
    }
}
