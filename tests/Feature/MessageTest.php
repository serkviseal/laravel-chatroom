<?php

use App\Events\MessageCreated;
use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('unauthenticated users cannot send messages', function () {
    $room = Room::factory()->create();
    $this->postJson('/api/v1/messages', ['body' => 'Hello', 'room_id' => $room->id])
        ->assertUnauthorized();
});

test('user can send a message to a room', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $room->join($user);

    $this->actingAs($user)
        ->postJson('/api/v1/messages', ['body' => 'Hello world', 'room_id' => $room->id])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Hello world');

    $this->assertDatabaseHas('messages', ['body' => 'Hello world', 'room_id' => $room->id]);
});

test('sending a message broadcasts MessageCreated event', function () {
    Event::fake();
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $room->join($user);

    $this->actingAs($user)
        ->postJson('/api/v1/messages', ['body' => 'Broadcast test', 'room_id' => $room->id]);

    Event::assertDispatched(MessageCreated::class);
});

test('user can edit their own message', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $message = Message::factory()->create(['user_id' => $user->id, 'room_id' => $room->id]);

    $this->actingAs($user)
        ->putJson("/api/v1/messages/{$message->id}", ['body' => 'Updated body'])
        ->assertOk()
        ->assertJsonPath('data.body', 'Updated body');

    expect($message->fresh()->edited_at)->not->toBeNull();
});

test('user cannot edit another users message', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $room = Room::factory()->create();
    $message = Message::factory()->create(['user_id' => $owner->id, 'room_id' => $room->id]);

    $this->actingAs($other)
        ->putJson("/api/v1/messages/{$message->id}", ['body' => 'Hack'])
        ->assertForbidden();
});

test('user can delete their own message', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $message = Message::factory()->create(['user_id' => $user->id, 'room_id' => $room->id]);

    $this->actingAs($user)
        ->deleteJson("/api/v1/messages/{$message->id}")
        ->assertOk();

    expect($message->fresh()->deleted_at)->not->toBeNull();
});

test('user can react to a message', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $message = Message::factory()->create(['room_id' => $room->id]);

    $this->actingAs($user)
        ->postJson("/api/v1/messages/{$message->id}/react", ['emoji' => '👍'])
        ->assertOk();

    $this->assertDatabaseHas('reactions', [
        'message_id' => $message->id,
        'user_id' => $user->id,
        'emoji' => '👍',
    ]);
});

test('reacting twice with same emoji removes the reaction', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $message = Message::factory()->create(['room_id' => $room->id]);

    $this->actingAs($user)->postJson("/api/v1/messages/{$message->id}/react", ['emoji' => '👍']);
    $this->actingAs($user)->postJson("/api/v1/messages/{$message->id}/react", ['emoji' => '👍']);

    $this->assertDatabaseMissing('reactions', ['message_id' => $message->id, 'user_id' => $user->id]);
});

test('message body is required when no attachment', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/messages', ['room_id' => $room->id])
        ->assertUnprocessable();
});
