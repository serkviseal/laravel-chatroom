<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly User $user,
        private readonly array $data,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('workspace.'.$this->data['workspace_id']);
    }

    public function broadcastAs(): string
    {
        return 'user.status';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'status' => $this->data['status'],
            'status_emoji' => $this->data['status_emoji'] ?? null,
            'status_text' => $this->data['status_text'] ?? null,
        ];
    }
}
