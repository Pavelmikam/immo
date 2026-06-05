<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    public function via(object $notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreferences();

        if (!$prefs->isTypeEnabled($this->getType())) {
            return [];
        }

        $channels = $prefs->getActiveChannels($this->getType());

        // Fallback : si le type est actif mais tous les canaux sont désactivés
        return !empty($channels) ? $channels : ['database'];
    }

    abstract public function getType(): string;

    abstract public function toDatabase(object $notifiable): array;

    abstract public function toMail(object $notifiable): MailMessage;

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
