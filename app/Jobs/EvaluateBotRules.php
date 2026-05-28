<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\WhatsAppConversation;
use App\Services\BotRuleEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateBotRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        private int $messageId,
        private int $conversationId
    ) {}

    public function handle(BotRuleEngine $engine): void
    {
        $message      = Message::find($this->messageId);
        $conversation = WhatsAppConversation::with(['contact', 'whatsappAccount', 'room'])->find($this->conversationId);

        if (!$message || !$conversation) {
            return;
        }

        $action = $engine->evaluate($message, $conversation);

        if ($action) {
            $engine->executeAction($action, $message, $conversation);
        }
    }
}
