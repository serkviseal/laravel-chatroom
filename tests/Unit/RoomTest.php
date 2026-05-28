<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can join a room', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $room->join($user);

    $found = $room->users()->where('users.id', $user->id)->first();

    expect($found)->toBeInstanceOf(User::class)
        ->and($found->id)->toBe($user->id);
});

test('joining a room twice does not duplicate membership', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $room->join($user);
    $room->join($user);

    expect($room->users()->where('users.id', $user->id)->count())->toBe(1);
});

test('user can leave a room', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $room->join($user);

    $room->leave($user);

    expect($room->users()->where('users.id', $user->id)->exists())->toBeFalse();
});

test('room scope search filters by name', function () {
    Room::factory()->create(['name' => 'backend chat']);
    Room::factory()->create(['name' => 'frontend chat']);
    Room::factory()->create(['name' => 'general']);

    $results = Room::where('name', 'like', '%backend%')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('backend chat');
});
