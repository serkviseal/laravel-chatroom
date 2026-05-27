<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Private notification channel per user
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

// Presence channel for a chat room — returns user data on join
Broadcast::channel('room.{roomId}', function (User $user, int $roomId) {
    if ($user->hasJoined($roomId)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
        ];
    }
});

// Private channel for 1-on-1 direct messages
Broadcast::channel('dm.{a}-{b}', function (User $user, int $a, int $b) {
    return in_array($user->id, [$a, $b]);
});
