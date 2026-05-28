<?php

use App\Events\UserStatusChanged;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('me returns authenticated user with workspaces', function () {
    $user = User::factory()->create(['name' => 'Alice']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('data.name', 'Alice');
});

test('unauthenticated user cannot access me endpoint', function () {
    $this->getJson('/api/v1/user')->assertUnauthorized();
});

test('user can update their name', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/user', ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    expect($user->fresh()->name)->toBe('New Name');
});

test('name update validates max length', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/user', ['name' => str_repeat('x', 101)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('user can update their status', function () {
    Event::fake([UserStatusChanged::class]);

    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->members()->attach($user, ['role' => 'owner', 'joined_at' => now()]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/user/status', [
            'workspace_id' => $workspace->id,
            'status' => 'online',
            'status_emoji' => '🟢',
            'status_text' => 'Working',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'online')
        ->assertJsonPath('status_emoji', '🟢')
        ->assertJsonPath('status_text', 'Working');

    Event::assertDispatched(UserStatusChanged::class);
});

test('status update rejects invalid status value', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/user/status', [
            'workspace_id' => $workspace->id,
            'status' => 'invisible',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});
