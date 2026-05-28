<?php

use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function wsAdmin(): array
{
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $ws->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

    return [$owner, $ws];
}

// ── index ─────────────────────────────────────────────────────────────────────

test('member can list whatsapp accounts', function () {
    [$owner, $ws] = wsAdmin();

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/whatsapp-accounts")
        ->assertOk();
});

test('non-member cannot list whatsapp accounts', function () {
    $outsider = User::factory()->create();
    $ws = Workspace::factory()->create();

    $this->actingAs($outsider, 'sanctum')
        ->getJson("/api/v1/workspaces/{$ws->id}/whatsapp-accounts")
        ->assertForbidden();
});

// ── store ─────────────────────────────────────────────────────────────────────

test('owner can create a whatsapp account', function () {
    [$owner, $ws] = wsAdmin();

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/whatsapp-accounts", [
            'display_name' => 'Support Line',
            'access_token' => 'tok_abc123',
            'provider' => 'meta',
            'phone_number_id' => '1234567890',
        ])
        ->assertCreated()
        ->assertJsonPath('account.display_name', 'Support Line');
});

test('whatsapp account creation validates required fields', function () {
    [$owner, $ws] = wsAdmin();

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/whatsapp-accounts", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['display_name', 'access_token']);
});

test('member cannot create whatsapp account', function () {
    [$owner, $ws] = wsAdmin();
    $member = User::factory()->create();
    $ws->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($member, 'sanctum')
        ->postJson("/api/v1/workspaces/{$ws->id}/whatsapp-accounts", [
            'display_name' => 'Line',
            'access_token' => 'tok',
        ])
        ->assertForbidden();
});

// ── update ────────────────────────────────────────────────────────────────────

test('owner can update a whatsapp account', function () {
    [$owner, $ws] = wsAdmin();
    $account = WhatsAppAccount::create([
        'workspace_id' => $ws->id,
        'display_name' => 'Old Name',
        'access_token' => 'tok',
        'verify_token' => 'vt',
        'is_active' => true,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->putJson("/api/v1/workspaces/{$ws->id}/whatsapp-accounts/{$account->id}", [
            'display_name' => 'New Name',
        ])
        ->assertOk()
        ->assertJsonPath('display_name', 'New Name');
});

// ── destroy ───────────────────────────────────────────────────────────────────

test('owner can delete a whatsapp account', function () {
    [$owner, $ws] = wsAdmin();
    $account = WhatsAppAccount::create([
        'workspace_id' => $ws->id,
        'display_name' => 'Line',
        'access_token' => 'tok',
        'verify_token' => 'vt',
        'is_active' => true,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/workspaces/{$ws->id}/whatsapp-accounts/{$account->id}")
        ->assertNoContent();

    expect(WhatsAppAccount::find($account->id))->toBeNull();
});
