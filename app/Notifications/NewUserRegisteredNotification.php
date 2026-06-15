<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class NewUserRegisteredNotification extends BaseNotification
{
    public function __construct(private readonly User $newUser) {}

    public function getType(): string
    {
        return 'new_user';
    }

    public function toDatabase(object $notifiable): array
    {
        $role = $this->newUser->role === 'proprietaire' ? 'Propriétaire' : 'Client (locataire)';

        return [
            'type'        => 'new_user',
            'title'       => 'Nouvel utilisateur inscrit',
            'body'        => "{$this->newUser->name} vient de créer un compte ({$role}).",
            'user_id'     => $this->newUser->id,
            'user_role'   => $this->newUser->role,
            'action_url'  => "/admin/users/{$this->newUser->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $role = $this->newUser->role === 'proprietaire' ? 'Propriétaire' : 'Client (locataire)';

        return (new MailMessage)
            ->subject('Nouvel utilisateur inscrit — ImmoConnect')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$this->newUser->name} vient de créer un compte sur ImmoConnect.")
            ->line("Rôle : {$role}")
            ->line("Email : {$this->newUser->email}")
            ->action('Voir le profil', url("/admin/users/{$this->newUser->id}"));
    }
}
