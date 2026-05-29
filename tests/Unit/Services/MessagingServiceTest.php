<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\MessagingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesConversations;
use Tests\Traits\CreatesProperties;

class MessagingServiceTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesConversations;

    private MessagingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MessagingService();
    }

    public function test_findOrCreate_cree_nouvelle_conversation(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();

        $this->service->findOrCreateConversation($tenant, $property);

        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('conversation_participants', 2);
    }

    public function test_findOrCreate_retourne_existante_si_deja_presente(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();

        $this->service->findOrCreateConversation($tenant, $property);
        $this->service->findOrCreateConversation($tenant, $property);

        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_findOrCreate_leve_exception_si_proprietaire_initie(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);

        $this->expectException(DomainException::class);

        $this->service->findOrCreateConversation($owner, $property);
    }

    public function test_sendMessage_incremente_unread_pour_autres_participants(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $conv     = $this->createConversation($tenant, $property);

        $this->service->sendMessage($conv, $tenant, 'Hello owner!');

        $ownerPivot  = $conv->fresh()->participants()->where('user_id', $owner->id)->first()->pivot;
        $tenantPivot = $conv->fresh()->participants()->where('user_id', $tenant->id)->first()->pivot;

        $this->assertEquals(1, $ownerPivot->unread_count);
        $this->assertEquals(0, $tenantPivot->unread_count);
    }

    public function test_sendMessage_met_a_jour_snapshot(): void
    {
        $tenant = $this->makeTenant();
        $conv   = $this->createConversation($tenant);

        $this->service->sendMessage($conv, $tenant, 'Bonjour depuis le service.');

        $fresh = $conv->fresh();
        $this->assertNotNull($fresh->last_message_at);
        $this->assertStringContainsString('Bonjour', $fresh->last_message_preview);
    }

    public function test_markAsRead_remet_unread_count_a_zero(): void
    {
        $tenant = $this->makeTenant();
        $conv   = $this->createConversation($tenant);

        $this->setUnreadCount($conv, $tenant, 5);

        $this->service->markAsRead($conv, $tenant);

        $pivot = $conv->fresh()->participants()->where('user_id', $tenant->id)->first()->pivot;
        $this->assertEquals(0, $pivot->unread_count);
    }

    public function test_markAsRead_ne_fait_rien_si_non_participant(): void
    {
        $tiers = $this->makeTenant();
        $conv  = $this->createConversation();

        // Ne doit pas lancer d'exception
        $this->service->markAsRead($conv, $tiers);
        $this->assertTrue(true);
    }

    public function test_getTotalUnread_somme_tous_les_unread(): void
    {
        $tenant = $this->makeTenant();
        $conv1  = $this->createConversation($tenant);
        $conv2  = $this->createConversation($tenant);

        $this->setUnreadCount($conv1, $tenant, 3);
        $this->setUnreadCount($conv2, $tenant, 2);

        $total = $this->service->getTotalUnread($tenant);
        $this->assertEquals(5, $total);
    }

    public function test_archiveForUser_archive_seulement_pour_ce_user(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $conv     = $this->createConversation($tenant, $property);

        $this->service->archiveForUser($conv, $tenant);

        $tenantPivot = $conv->fresh()->participants()->where('user_id', $tenant->id)->first()->pivot;
        $ownerPivot  = $conv->fresh()->participants()->where('user_id', $owner->id)->first()->pivot;

        $this->assertTrue((bool) $tenantPivot->is_archived);
        $this->assertFalse((bool) $ownerPivot->is_archived);
    }

    protected function makeTenant(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $attrs));
    }
}
