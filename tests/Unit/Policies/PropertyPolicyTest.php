<?php

namespace Tests\Unit\Policies;

use App\Models\Property;
use App\Models\User;
use App\Policies\PropertyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PropertyPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PropertyPolicy();
    }

    public function test_admin_can_do_everything(): void
    {
        $admin    = User::factory()->admin()->create();
        $property = Property::factory()->for($admin, 'owner')->create(['status' => 'draft']);

        $this->assertTrue($this->policy->before($admin, 'view'));
        $this->assertNull($this->policy->before(User::factory()->proprietaire()->create(), 'view'));
    }

    public function test_proprietaire_can_create(): void
    {
        $proprietaire = User::factory()->proprietaire()->create();
        $this->assertTrue($this->policy->create($proprietaire));
    }

    public function test_locataire_cannot_create(): void
    {
        $locataire = User::factory()->locataire()->create();
        $this->assertFalse($this->policy->create($locataire));
    }

    public function test_unverified_proprietaire_can_create_draft(): void
    {
        // Design: unverified proprietaires may create drafts; only submit requires verification.
        $unverified = User::factory()->proprietaire()->unverified()->create();
        $this->assertTrue($this->policy->create($unverified));
    }

    public function test_owner_can_update_draft(): void
    {
        $owner    = User::factory()->proprietaire()->create();
        $property = Property::factory()->for($owner, 'owner')->create(['status' => 'draft']);

        $this->assertTrue($this->policy->update($owner, $property));
    }

    public function test_owner_cannot_update_active(): void
    {
        $owner    = User::factory()->proprietaire()->create();
        $property = Property::factory()->for($owner, 'owner')->active()->create();

        $this->assertFalse($this->policy->update($owner, $property));
    }

    public function test_non_owner_cannot_update(): void
    {
        $owner    = User::factory()->proprietaire()->create();
        $other    = User::factory()->proprietaire()->create();
        $property = Property::factory()->for($owner, 'owner')->create(['status' => 'draft']);

        $this->assertFalse($this->policy->update($other, $property));
    }

    public function test_owner_can_delete_non_active_property(): void
    {
        $owner    = User::factory()->proprietaire()->create();
        $property = Property::factory()->for($owner, 'owner')->create(['status' => 'draft']);

        $this->assertTrue($this->policy->delete($owner, $property));
    }

    public function test_owner_cannot_delete_active_property(): void
    {
        $owner    = User::factory()->proprietaire()->create();
        $property = Property::factory()->for($owner, 'owner')->active()->create();

        $this->assertFalse($this->policy->delete($owner, $property));
    }

    public function test_guest_can_view_active_property(): void
    {
        $owner    = User::factory()->proprietaire()->create();
        $property = Property::factory()->for($owner, 'owner')->active()->create();

        $this->assertTrue($this->policy->view(null, $property));
    }

    public function test_guest_cannot_view_draft_property(): void
    {
        $owner    = User::factory()->proprietaire()->create();
        $property = Property::factory()->for($owner, 'owner')->create(['status' => 'draft']);

        $this->assertFalse($this->policy->view(null, $property));
    }
}
