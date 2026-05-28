<?php

use App\Models\Room;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeConversation(Workspace $ws, User $owner): WhatsAppConversation
{
    $account = WhatsAppAccount::create([
        'workspace_id' => $ws->id,
        'display_name' => 'Support',
        'access_token' => 'tok',
        'verify_token' => 'vt',
        'is_active' => true,
    ]);

    $contact = WhatsAppContact::create([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'phone' => '+15550000001',
        'display_name' => 'Client',
    ]);

    $room = Room::factory()->create(['workspace_id' => $ws->id]);

    return WhatsAppConversation::create([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'contact_id' => $contact->id,
        'room_id' => $room->id,
        'status' => 'open',
    ]);
}

// ── index ─────────────────────────────────────────────────────────────────────

test('member can list inbox conversations', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/inbox")
        ->assertOk();
});

test('non-member cannot view inbox', function () {
    $outsider = User::factory()->create();
    $ws = Workspace::factory()->create();

    $this->actingAs($outsider, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/inbox")
        ->assertForbidden();
});

test('inbox filters by status', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    makeConversation($ws, $owner);

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/inbox?status=resolved")
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/inbox?status=open")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('inbox filters by all status returns all', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    makeConversation($ws, $owner);

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/inbox?status=all")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('inbox filters by assigned to me', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    $conv = makeConversation($ws, $owner);
    $conv->update(['assigned_agent_id' => $owner->id]);

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/inbox?assigned_to=me")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('inbox filters by unassigned', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    makeConversation($ws, $owner);

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/inbox?assigned_to=unassigned")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

// ── assign ────────────────────────────────────────────────────────────────────

test('admin can assign a conversation to an agent', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    $conv = makeConversation($ws, $owner);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/conversations/{$conv->id}/assign", [
            'workspace_id' => $ws->id,
            'agent_id' => $owner->id,
        ])
        ->assertOk();

    expect($conv->fresh()->assigned_agent_id)->toBe($owner->id);
});

// ── updateStatus ──────────────────────────────────────────────────────────────

test('admin can resolve a conversation', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    $conv = makeConversation($ws, $owner);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/conversations/{$conv->id}/status", [
            'workspace_id' => $ws->id,
            'status' => 'resolved',
        ])
        ->assertOk();

    expect($conv->fresh()->status)->toBe('resolved');
    expect($conv->fresh()->resolved_at)->not->toBeNull();
});

test('admin can snooze a conversation', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
    $conv = makeConversation($ws, $owner);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/conversations/{$conv->id}/status", [
            'workspace_id' => $ws->id,
            'status' => 'snoozed',
            'snoozed_until' => now()->addHour()->toISOString(),
        ])
        ->assertOk();

    expect($conv->fresh()->status)->toBe('snoozed');
});
