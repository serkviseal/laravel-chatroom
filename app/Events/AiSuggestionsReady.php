<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiSuggestionsReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int   $conversationId,
        public array $suggestions
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("conversation.{$this->conversationId}");
    }

    public function broadcastAs(): string
    {
        return 'ai.suggestions.ready';
    }
}
