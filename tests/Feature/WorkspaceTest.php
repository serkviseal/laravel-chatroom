<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
use App\Models\Workspace;

it('creates a workspace and becomes owner', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $resp = $this->postJson('/api/v1/workspaces', ['name' => 'Acme Corp']);

    $resp->assertCreated()->assertJsonPath('data.role', 'owner');
    expect(Workspace::where('slug', 'acme-corp')->exists())->toBeTrue();
});

it('lists only workspaces the user belongs to', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $ws = Workspace::factory()->create(['owner_id' => $alice->id]);
    $ws->members()->attach($alice, ['role' => 'owner', 'joined_at' => now()]);

    $this->actingAs($alice, 'sanctum');
    $this->getJson('/api/v1/workspaces')->assertJsonCount(1, 'data');

    $this->actingAs($bob, 'sanctum');
    $this->getJson('/api/v1/workspaces')->assertJsonCount(0, 'data');
});

it('allows a user to join a workspace', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();

    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

    $this->actingAs($user, 'sanctum');
    $this->postJson("/api/v1/workspaces/{$ws->id}/join")->assertOk();
    expect($ws->hasMember($user))->toBeTrue();
});

it('returns 403 when non-owner tries to update workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    $ws->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($member, 'sanctum');
    $this->putJson("/api/v1/workspaces/{$ws->id}", ['name' => 'Hacked'])->assertForbidden();
});

it('prevents owner from leaving workspace', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

    $this->actingAs($owner, 'sanctum');
    $this->postJson("/api/v1/workspaces/{$ws->id}/leave")->assertUnprocessable();
});
