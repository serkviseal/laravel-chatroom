<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can add a room', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $user->addRoom($room);

    expect($user->rooms()->where('rooms.id', $room->id)->exists())->toBeTrue();
});

test('hasJoined returns true when user has joined', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $room->join($user);

    expect($user->hasJoined($room->id))->toBeTrue();
});

test('hasJoined returns false when user has not joined', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    expect($user->hasJoined($room->id))->toBeFalse();
});

test('avatar url falls back to ui-avatars when no avatar set', function () {
    $user = User::factory()->create(['name' => 'Alice Test', 'avatar' => null]);

    expect($user->avatar_url)->toContain('ui-avatars.com');
});
