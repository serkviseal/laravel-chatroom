<?php

namespace App\Http\Resources;

use App\Services\MessageParser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'body_html' => $this->bodyHtml(),
            'type' => $this->type,
            'attachment_url' => $this->attachment_path ? asset('storage/'.$this->attachment_path) : null,
            'user' => new UserResource($this->whenLoaded('user')),
            'room_id' => $this->room_id,
            'workspace_id' => $this->workspace_id,
            'thread_id' => $this->thread_id,
            'is_thread_reply' => (bool) $this->is_thread_reply,
            'is_pinned' => (bool) $this->is_pinned,
            'reply_count' => $this->when(! $this->is_thread_reply, fn () => $this->replyCount()),
            'reactions' => $this->reactionsGrouped(),
            'is_mine' => $this->user_id === $request->user()?->id,
            'edited_at' => $this->edited_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }

    private function bodyHtml(): ?string
    {
        if (! $this->body) {
            return null;
        }
        $key = "msg_html_{$this->id}_".($this->updated_at?->timestamp ?? 0);

        return Cache::remember($key, 3600, function () {
            return app(MessageParser::class)->toHtml($this->body);
        });
    }
}
