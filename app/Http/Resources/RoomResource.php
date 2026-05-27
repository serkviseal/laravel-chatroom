<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'member_count' => $this->whenLoaded('users', fn () => $this->users->count()),
            'last_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'joined' => $request->user()?->hasJoined($this->id) ?? false,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
