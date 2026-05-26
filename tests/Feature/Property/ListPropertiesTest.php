<?php

namespace Tests\Feature\Property;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class ListPropertiesTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_guest_can_list_active_properties(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(3)->create();
        Property::factory()->for($owner, 'owner')->pending()->count(2)->create();

        $response = $this->getJson('/api/properties');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_returns_paginated_results(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(20)->create();

        $response = $this->getJson('/api/properties?per_page=5');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'meta', 'links']);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_filter_by_type(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['type' => 'apartment']);
        Property::factory()->for($owner, 'owner')->active()->create(['type' => 'villa']);
        Property::factory()->for($owner, 'owner')->active()->create(['type' => 'villa']);

        $response = $this->getJson('/api/properties?type=villa');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filter_by_transaction_type(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->forRent()->count(3)->create();
        Property::factory()->for($owner, 'owner')->active()->forSale()->count(2)->create();

        $response = $this->getJson('/api/properties?transaction_type=rent');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_filter_by_city(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['city' => 'Yaoundé']);
        Property::factory()->for($owner, 'owner')->active()->create(['city' => 'Douala']);

        $response = $this->getJson('/api/properties?city=Douala');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_featured_properties_appear_first(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(3)->create(['is_featured' => false]);
        $featured = Property::factory()->for($owner, 'owner')->featured()->create();

        $response = $this->getJson('/api/properties');

        $this->assertEquals($featured->id, $response->json('data.0.id'));
    }
}
