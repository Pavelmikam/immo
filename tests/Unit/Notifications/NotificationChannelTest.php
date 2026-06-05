<?php

namespace Tests\Unit\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\RentalRequestReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    private function makePrefs(array $channels = [], array $enabledTypes = []): NotificationPreference
    {
        $user  = User::factory()->create(['is_active' => true]);
        $prefs = new NotificationPreference([
            'user_id'       => $user->id,
            'channels'      => array_merge(['mail' => true, 'database' => true], $channels),
            'enabled_types' => $enabledTypes,
        ]);
        $prefs->save();

        return $prefs;
    }

    public function test_via_retourne_mail_et_database_par_defaut(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $prefs = $user->getOrCreateNotificationPreferences();

        $channels = $prefs->getActiveChannels('rental_request_received');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_via_retourne_seulement_database_si_mail_desactive(): void
    {
        $prefs = $this->makePrefs(['mail' => false]);

        $channels = $prefs->getActiveChannels('rental_request_received');

        $this->assertNotContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_via_retourne_tableau_vide_si_type_desactive(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $prefs = $user->getOrCreateNotificationPreferences();
        $prefs->update(['enabled_types' => ['rental_request_received' => false]]);

        // getActiveChannels retourne [] quand le type est désactivé
        $channels = $prefs->fresh()->getActiveChannels('rental_request_received');
        $this->assertEmpty($channels);

        // via() retourne [] (aucune notif) quand le type est désactivé
        $notif = new RentalRequestReceivedNotification(
            \App\Models\RentalRequest::factory()->create([
                'tenant_id' => $user->id,
            ])
        );

        $this->assertEquals([], $notif->via($user));
    }

    public function test_isTypeEnabled_retourne_true_si_absent_des_prefs(): void
    {
        $prefs = $this->makePrefs([], []);

        $this->assertTrue($prefs->isTypeEnabled('message_received'));
        $this->assertTrue($prefs->isTypeEnabled('anything'));
    }

    public function test_isTypeEnabled_retourne_false_si_explicitement_desactive(): void
    {
        $prefs = $this->makePrefs([], ['message_received' => false]);

        $this->assertFalse($prefs->isTypeEnabled('message_received'));
    }

    public function test_getActiveChannels_retourne_tableau_vide_si_type_desactive(): void
    {
        $prefs = $this->makePrefs(['mail' => true, 'database' => true], ['message_received' => false]);

        $channels = $prefs->getActiveChannels('message_received');

        $this->assertEmpty($channels);
    }

    public function test_getActiveChannels_retourne_canaux_actifs(): void
    {
        $prefs = $this->makePrefs(['mail' => true, 'database' => true], []);

        $channels = $prefs->getActiveChannels('message_received');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }
}
