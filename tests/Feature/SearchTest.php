<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeWorkspaceWithMember(): array
{
    $user = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $user->id]);
    $ws->members()->attach($user, ['role' => 'owner', 'joined_at' => now()]);

    return [$user, $ws];
}

test('search requires authentication', function () {
    $ws = Workspace::factory()->create();

    $this->getJson("/api/v1/workspaces/{$ws->id}/search?q=hello")
        ->assertUnauthorized();
});

test('non-member cannot search workspace', function () {
    $outsider = User::factory()->create();
    $ws = Workspace::factory()->create();

    $this->actingAs($outsider, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/search?q=hello")
        ->assertForbidden();
});

test('search requires minimum 2 character query', function () {
    [$user, $ws] = makeWorkspaceWithMember();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/search?q=a")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
});

test('search rejects invalid type filter', function () {
    [$user, $ws] = makeWorkspaceWithMember();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/search?q=hello&type=invalid")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('type');
});

test('search returns results structure for all types', function () {
    [$user, $ws] = makeWorkspaceWithMember();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/search?q=hello")
        ->assertOk()
        ->assertJsonStructure(['data', 'query'])
        ->assertJsonPath('query', 'hello');
});

test('search with messages type filter only searches messages', function () {
    [$user, $ws] = makeWorkspaceWithMember();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/search?q=hello&type=messages")
        ->assertOk()
        ->assertJsonPath('query', 'hello');
});

test('search with channels type filter only searches channels', function () {
    [$user, $ws] = makeWorkspaceWithMember();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/search?q=hello&type=channels")
        ->assertOk()
        ->assertJsonPath('query', 'hello');
});

test('search with files type filter only searches files', function () {
    [$user, $ws] = makeWorkspaceWithMember();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/search?q=hello&type=files")
        ->assertOk()
        ->assertJsonPath('query', 'hello');
});
