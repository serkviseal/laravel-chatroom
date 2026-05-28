<?php

use App\Models\BotRule;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function wsOwner(): array
{
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

    return [$owner, $ws];
}

// ── index ─────────────────────────────────────────────────────────────────────

test('member can list bot rules', function () {
    [$owner, $ws] = wsOwner();

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/bot-rules")
        ->assertOk();
});

// ── store ─────────────────────────────────────────────────────────────────────

test('owner can create a bot rule', function () {
    [$owner, $ws] = wsOwner();

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/bot-rules", [
            'name' => 'Keyword Reply',
            'trigger_type' => 'keyword',
            'trigger_value' => 'hello',
            'action_type' => 'reply',
            'action_value' => ['text' => 'Hi there!'],
        ])
        ->assertCreated()
        ->assertJsonPath('name', 'Keyword Reply');

    expect($ws->botRules()->where('name', 'Keyword Reply')->exists())->toBeTrue();
});

test('bot rule store validates required fields', function () {
    [$owner, $ws] = wsOwner();

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/bot-rules", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'trigger_type', 'action_type']);
});

test('member cannot create bot rules', function () {
    [$owner, $ws] = wsOwner();
    $member = User::factory()->create();
    $ws->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($member, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/bot-rules", [
            'name' => 'Rule',
            'trigger_type' => 'keyword',
            'action_type' => 'reply',
        ])
        ->assertForbidden();
});

// ── update ────────────────────────────────────────────────────────────────────

test('owner can update a bot rule', function () {
    [$owner, $ws] = wsOwner();
    $rule = BotRule::create([
        'workspace_id' => $ws->id,
        'name' => 'Old Name',
        'trigger_type' => 'keyword',
        'action_type' => 'reply',
        'priority' => 10,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/workspaces/{$ws->id}/bot-rules/{$rule->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('name', 'New Name');
});

// ── destroy ───────────────────────────────────────────────────────────────────

test('owner can delete a bot rule', function () {
    [$owner, $ws] = wsOwner();
    $rule = BotRule::create([
        'workspace_id' => $ws->id,
        'name' => 'Rule',
        'trigger_type' => 'always',
        'action_type' => 'reply',
        'priority' => 10,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/workspaces/{$ws->id}/bot-rules/{$rule->id}")
        ->assertNoContent();

    expect(BotRule::find($rule->id))->toBeNull();
});

// ── reorder ───────────────────────────────────────────────────────────────────

test('owner can reorder bot rules', function () {
    [$owner, $ws] = wsOwner();
    $r1 = BotRule::create(['workspace_id' => $ws->id, 'name' => 'R1', 'trigger_type' => 'keyword', 'action_type' => 'reply', 'priority' => 10]);
    $r2 = BotRule::create(['workspace_id' => $ws->id, 'name' => 'R2', 'trigger_type' => 'always', 'action_type' => 'reply', 'priority' => 20]);

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/bot-rules/reorder", [
            'order' => [$r2->id, $r1->id],
        ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect(BotRule::find($r2->id)->priority)->toBe(10);
    expect(BotRule::find($r1->id)->priority)->toBe(20);
});
