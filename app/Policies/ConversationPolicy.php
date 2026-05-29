<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user) || $user->isAdmin();
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        return $conversation->participants()
                            ->where('user_id', $user->id)
                            ->whereNull('left_at')
                            ->exists();
    }

    public function archive(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user);
    }
}
