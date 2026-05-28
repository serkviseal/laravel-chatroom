<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function update(User $user, Message $message): bool
    {
        if ($message->user_id === $user->id) {
            return true;
        }
        if ($message->workspace_id === null) {
            return false;
        }

        return in_array($user->roleIn($message->workspace), ['owner', 'admin']);
    }

    public function delete(User $user, Message $message): bool
    {
        if ($message->user_id === $user->id) {
            return true;
        }
        if ($message->workspace_id === null) {
            return false;
        }

        return in_array($user->roleIn($message->workspace), ['owner', 'admin']);
    }

    public function pin(User $user, Message $message): bool
    {
        if ($message->workspace_id === null) {
            return false;
        }

        return in_array($user->roleIn($message->workspace), ['owner', 'admin']);
    }
}
