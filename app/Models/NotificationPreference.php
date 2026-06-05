<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'channels', 'enabled_types'];

    protected $casts = [
        'channels'      => 'array',
        'enabled_types' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTypeEnabled(string $type): bool
    {
        return ($this->enabled_types ?? [])[$type] ?? true;
    }

    public function isChannelEnabled(string $channel): bool
    {
        return ($this->channels ?? [])[$channel] ?? true;
    }

    public function getActiveChannels(string $type): array
    {
        if (!$this->isTypeEnabled($type)) {
            return [];
        }

        $active = [];
        foreach (['mail', 'database'] as $channel) {
            if ($this->isChannelEnabled($channel)
                && config("notifications.channels.{$channel}", true)) {
                $active[] = $channel;
            }
        }

        return $active;
    }
}
