<?php

use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
use App\Models\Room;
use App\Models\User;

it('can reply to a message in a thread', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $room->join($user);

    $parent = Message::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/rooms/{$room->id}/messages/{$parent->id}/replies", [
            'body' => 'This is a thread reply',
        ])
        ->assertCreated()
        ->assertJsonPath('data.thread_id', $parent->id)
        ->assertJsonPath('data.is_thread_reply', true);
});

it('can list replies for a message', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $parent = Message::factory()->create(['user_id' => $user->id, 'room_id' => $room->id]);
    Message::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'thread_id' => $parent->id,
        'is_thread_reply' => true,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/rooms/{$room->id}/messages/{$parent->id}/replies")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('thread roots do not include replies', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $room->join($user);

    $parent = Message::factory()->create(['user_id' => $user->id, 'room_id' => $room->id]);
    Message::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'thread_id' => $parent->id,
        'is_thread_reply' => true,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/rooms/{$room->id}/messages")
        ->assertJsonCount(1, 'data');
});
