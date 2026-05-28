<?php

use App\Models\Bot;
use App\Models\Room;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function workspaceWithOwner(): array
{
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

    return [$owner, $ws];
}

// ── index ─────────────────────────────────────────────────────────────────────

test('owner can list workspace bots', function () {
    [$owner, $ws] = workspaceWithOwner();

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/bots")
        ->assertOk();
});

test('member cannot list workspace bots', function () {
    [$owner, $ws] = workspaceWithOwner();
    $member = User::factory()->create();
    $ws->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($member, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/bots")
        ->assertForbidden();
});

// ── store ─────────────────────────────────────────────────────────────────────

test('owner can create a bot', function () {
    [$owner, $ws] = workspaceWithOwner();

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/bots", [
            'name' => 'My Bot',
            'description' => 'A helper bot',
        ])
        ->assertCreated()
        ->assertJsonPath('name', 'My Bot');

    expect($ws->bots()->where('name', 'My Bot')->exists())->toBeTrue();
});

test('bot creation generates an auth token', function () {
    [$owner, $ws] = workspaceWithOwner();

    $response = $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/bots", ['name' => 'Token Bot'])
        ->assertCreated();

    expect($response->json('auth_token'))->not->toBeNull()->toBeString();
});

test('bot name is required', function () {
    [$owner, $ws] = workspaceWithOwner();

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/bots", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

// ── regenerateToken ───────────────────────────────────────────────────────────

test('owner can regenerate bot token', function () {
    [$owner, $ws] = workspaceWithOwner();
    $bot = $ws->bots()->create(['name' => 'Bot', 'auth_token' => Bot::generateToken()]);
    $oldToken = $bot->auth_token;

    $response = $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/bots/{$bot->id}/token")
        ->assertOk();

    expect($response->json('auth_token'))->not->toBe($oldToken);
});

// ── destroy ───────────────────────────────────────────────────────────────────

test('owner can delete a bot', function () {
    [$owner, $ws] = workspaceWithOwner();
    $bot = $ws->bots()->create(['name' => 'Bot', 'auth_token' => Bot::generateToken()]);

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/workspaces/{$ws->id}/bots/{$bot->id}")
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect(Bot::find($bot->id))->toBeNull();
});

// ── incoming webhook ──────────────────────────────────────────────────────────

test('incoming webhook creates a message with generic payload', function () {
    [$owner, $ws] = workspaceWithOwner();
    $room = Room::factory()->create(['workspace_id' => $ws->id]);
    $token = Bot::generateToken();
    $ws->bots()->create([
        'name' => 'WH Bot',
        'auth_token' => $token,
        'is_active' => true,
        'channel_ids' => null,
        'subscribed_events' => ['message.created'],
    ]);

    $this->postJson("/api/webhooks/incoming/{$token}", [
        'body' => 'Hello from outside',
        'channel_id' => $room->id,
    ])->assertOk()->assertJsonPath('ok', true);

    $this->assertDatabaseHas('messages', ['body' => 'Hello from outside', 'room_id' => $room->id]);
});

test('incoming webhook returns 401 for unknown token', function () {
    $this->postJson('/api/webhooks/incoming/badtoken', [
        'body' => 'test',
        'channel_id' => 1,
    ])->assertNotFound();
});

test('incoming webhook rejects unrecognised payload', function () {
    [$owner, $ws] = workspaceWithOwner();
    $token = Bot::generateToken();
    $ws->bots()->create(['name' => 'Bot', 'auth_token' => $token, 'is_active' => true]);

    $this->postJson("/api/webhooks/incoming/{$token}", ['unknown' => 'data'])
        ->assertUnprocessable();
});
