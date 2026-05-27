<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'type' => $this->type,
            'attachment_url' => $this->attachment_path ? asset('storage/'.$this->attachment_path) : null,
            'user' => new UserResource($this->whenLoaded('user')),
            'room_id' => $this->room_id,
            'reactions' => $this->reactionsGrouped(),
            'is_mine' => $this->user_id === $request->user()?->id,
            'edited_at' => $this->edited_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
