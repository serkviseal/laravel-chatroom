<?php

namespace App\Policies;

use App\Models\StoredFile;
use App\Models\User;

class FilePolicy
{
    public function view(User $user, StoredFile $file): bool
    {
        $workspace = $file->workspace;

        return $workspace !== null && $workspace->hasMember($user);
    }

    public function delete(User $user, StoredFile $file): bool
    {
        if ($file->uploader_id === $user->id) {
            return true;
        }

        $workspace = $file->workspace;

        return $workspace !== null && in_array($user->roleIn($workspace), ['owner', 'admin']);
    }
}
