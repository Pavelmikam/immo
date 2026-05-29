<?php

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesConversations;
use Tests\Traits\CreatesProperties;

class ConversationModelTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesConversations;

    public function test_isParticipant_retourne_true_si_user_est_participant(): void
    {
        $tenant = $this->makeTenant();
        $conv   = $this->createConversation($tenant);

        $this->assertTrue($conv->isParticipant($tenant));
    }

    public function test_isParticipant_retourne_false_si_user_absent(): void
    {
        $tiers = $this->makeTenant();
        $conv  = $this->createConversation();

        $this->assertFalse($conv->isParticipant($tiers));
    }

    public function test_getUnreadCountFor_retourne_bon_compteur(): void
    {
        $tenant = $this->makeTenant();
        $conv   = $this->createConversation($tenant);

        $this->setUnreadCount($conv, $tenant, 7);

        $this->assertSame(7, $conv->fresh()->getUnreadCountFor($tenant));
    }

    public function test_scope_forUser_retourne_conversations_du_user(): void
    {
        $userA = $this->makeTenant();
        $userB = $this->makeTenant();

        $this->createConversation($userA);
        $this->createConversation($userA);
        $this->createConversation($userA);
        $this->createConversation($userB);
        $this->createConversation($userB);

        $this->assertEquals(3, Conversation::forUser($userA->id)->count());
    }

    public function test_scope_notArchivedForUser_exclut_archivees(): void
    {
        $tenant = $this->makeTenant();

        $conv1 = $this->createConversation($tenant);
        $conv2 = $this->createConversation($tenant);
        $conv3 = $this->createConversation($tenant);

        $conv3->participants()->updateExistingPivot($tenant->id, ['is_archived' => true]);

        $this->assertEquals(2, Conversation::notArchivedForUser($tenant->id)->count());
    }

    public function test_scope_withUnread_retourne_conversations_avec_nonlus(): void
    {
        $tenant = $this->makeTenant();

        $conv1 = $this->createConversation($tenant);
        $conv2 = $this->createConversation($tenant);

        $this->setUnreadCount($conv2, $tenant, 3);

        $this->assertEquals(1, Conversation::withUnread($tenant->id)->count());
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
