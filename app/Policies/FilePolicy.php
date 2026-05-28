<?php

namespace App\Policies;

use App\Models\StoredFile;
use App\Models\User;

class FilePolicy
{
    public function view(User $user, StoredFile $file): bool
    {
        return $file->workspace->hasMember($user);
    }

    public function delete(User $user, StoredFile $file): bool
    {
        if ($file->uploader_id === $user->id) {
            return true;
        }

        return in_array($user->roleIn($file->workspace), ['owner', 'admin']);
    }
}
