<?php

namespace App\Events;

use App\Models\Room;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $broadcastQueue = 'events:room-joined';

    public function __construct(public User $user, public Room $room) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('room.'.$this->room->id)];
    }

    public function broadcastAs(): string
    {
        return 'room.joined';
    }
}
