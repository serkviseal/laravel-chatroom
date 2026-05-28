<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return in_array($user->roleIn($workspace), ['owner', 'admin']);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->roleIn($workspace) === 'owner';
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return in_array($user->roleIn($workspace), ['owner', 'admin']);
    }

    public function manageBilling(User $user, Workspace $workspace): bool
    {
        return $user->roleIn($workspace) === 'owner';
    }
}
