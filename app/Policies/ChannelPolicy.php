<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class ChannelPolicy
{
    public function view(User $user, Room $room): bool
    {
        if ($room->workspace_id === null) {
            return true;
        }
        $workspace = $room->workspace;
        if (! $workspace?->hasMember($user)) {
            return false;
        }
        if ($room->type === 'private') {
            return $user->hasJoined($room->id);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Room $room): bool
    {
        if ($room->workspace_id === null) {
            return false;
        }

        return in_array($user->roleIn($room->workspace), ['owner', 'admin']);
    }

    public function delete(User $user, Room $room): bool
    {
        if ($room->workspace_id === null) {
            return false;
        }

        return in_array($user->roleIn($room->workspace), ['owner', 'admin']);
    }
}
