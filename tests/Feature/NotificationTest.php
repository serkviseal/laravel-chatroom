<?php

use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use App\Notifications\MentionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

it('creates notification when user is mentioned', function () {
    Notification::fake();

    $sender = User::factory()->create(['name' => 'alice']);
    $mentioned = User::factory()->create(['name' => 'bob']);
    $room = Room::factory()->create();

    $message = Message::factory()->create([
        'user_id' => $sender->id,
        'room_id' => $room->id,
        'body' => 'Hello @bob check this out',
    ]);

    $mentioned->notify(new MentionNotification($message));

    Notification::assertSentTo($mentioned, MentionNotification::class);
});

it('can mark notifications as read', function () {
    $user = User::factory()->create();
    $sender = User::factory()->create();
    $room = Room::factory()->create();

    $message = Message::factory()->create([
        'user_id' => $sender->id,
        'room_id' => $room->id,
        'body' => 'Test',
    ]);

    $user->notify(new MentionNotification($message));

    expect($user->unreadNotifications()->count())->toBe(1);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/notifications/read')
        ->assertOk();

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('returns notification count', function () {
    $user = User::factory()->create();
    $sender = User::factory()->create();
    $room = Room::factory()->create();

    $message = Message::factory()->create([
        'user_id' => $sender->id,
        'room_id' => $room->id,
        'body' => 'Test',
    ]);

    $user->notify(new MentionNotification($message));
    $user->notify(new MentionNotification($message));

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications/count')
        ->assertJsonPath('unread_count', 2);
});
