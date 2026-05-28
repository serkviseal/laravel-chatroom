<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── show ──────────────────────────────────────────────────────────────────────

test('member can view workspace details', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}")
        ->assertOk()
        ->assertJsonPath('data.name', $ws->name);
});

test('non-member cannot view workspace details', function () {
    $outsider = User::factory()->create();
    $ws = Workspace::factory()->create();

    $this->actingAs($outsider, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}")
        ->assertForbidden();
});

// ── update ────────────────────────────────────────────────────────────────────

test('owner can update workspace name', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/workspaces/{$ws->id}", ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');

    expect($ws->fresh()->name)->toBe('Renamed');
});

test('admin can update workspace', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    $ws->members()->attach($admin, ['role' => 'admin', 'joined_at' => now()]);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/workspaces/{$ws->id}", ['name' => 'Updated'])
        ->assertOk();
});

// ── members ───────────────────────────────────────────────────────────────────

test('member can list workspace members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    $ws->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/members")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('non-member cannot list workspace members', function () {
    $outsider = User::factory()->create();
    $ws = Workspace::factory()->create();

    $this->actingAs($outsider, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/members")
        ->assertForbidden();
});

// ── updateMemberRole ──────────────────────────────────────────────────────────

test('owner can update a member role', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    $ws->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/workspaces/{$ws->id}/members/{$member->id}/role", ['role' => 'admin'])
        ->assertOk()
        ->assertJsonPath('message', 'Role updated');

    expect($ws->members()->where('users.id', $member->id)->value('role'))->toBe('admin');
});

test('member cannot update roles', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    $ws->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($member, 'sanctum')
        ->putJson("/api/v1/workspaces/{$ws->id}/members/{$owner->id}/role", ['role' => 'member'])
        ->assertForbidden();
});

// ── removeMember ──────────────────────────────────────────────────────────────

test('owner can remove a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    $ws->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/workspaces/{$ws->id}/members/{$member->id}")
        ->assertOk();

    expect($ws->hasMember($member))->toBeFalse();
});

test('owner cannot remove themselves via remove endpoint', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/workspaces/{$ws->id}/members/{$owner->id}")
        ->assertUnprocessable();
});

// ── storageStats ──────────────────────────────────────────────────────────────

test('member can view workspace storage stats', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id, 'storage_quota_mb' => 100]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/storage")
        ->assertOk()
        ->assertJsonStructure(['used_mb', 'quota_mb', 'percent_used']);
});
