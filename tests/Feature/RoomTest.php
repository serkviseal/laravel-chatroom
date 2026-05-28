<?php

use App\Events\RoomJoined;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('unauthenticated users cannot list rooms', function () {
    $this->getJson('/api/v1/rooms')->assertUnauthorized();
});

test('authenticated users can list rooms', function () {
    $user = User::factory()->create();
    Room::factory(3)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/rooms')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'description', 'joined']]]);
});

test('user can create a room', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/rooms', ['name' => 'test-room', 'description' => 'A test room'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'test-room');

    $this->assertDatabaseHas('rooms', ['name' => 'test-room']);
});

test('room name must be unique', function () {
    $user = User::factory()->create();
    Room::factory()->create(['name' => 'existing-room']);

    $this->actingAs($user)
        ->postJson('/api/v1/rooms', ['name' => 'existing-room'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('creator automatically joins their room', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/rooms', ['name' => 'my-room'])
        ->assertCreated();

    $room = Room::find($response->json('data.id'));
    expect($room->users()->where('users.id', $user->id)->exists())->toBeTrue();
});

test('user can join an existing room', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/rooms/{$room->id}/join")
        ->assertOk();

    expect($user->hasJoined($room->id))->toBeTrue();
});

test('user can leave a room', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $room->join($user);

    $this->actingAs($user)
        ->postJson("/api/v1/rooms/{$room->id}/leave")
        ->assertOk();

    expect($user->fresh()->hasJoined($room->id))->toBeFalse();
});

test('rooms can be searched by name', function () {
    $user = User::factory()->create();
    Room::factory()->create(['name' => 'backend dev']);
    Room::factory()->create(['name' => 'frontend dev']);
    Room::factory()->create(['name' => 'general']);

    $this->actingAs($user)
        ->getJson('/api/v1/rooms?search=dev')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('joining a room dispatches RoomJoined event', function () {
    Event::fake();
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $this->actingAs($user)->postJson("/api/v1/rooms/{$room->id}/join");

    Event::assertDispatched(RoomJoined::class, fn ($e) => $e->user->id === $user->id && $e->room->id === $room->id
    );
});
