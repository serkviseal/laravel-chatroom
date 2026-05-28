<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\WhatsAppConversation;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 6; // Meta 1 msg/6s per conversation

    public function __construct(
        private int $messageId,
        private int $conversationId
    ) {}

    public function handle(): void
    {
        $message = Message::find($this->messageId);
        $conversation = WhatsAppConversation::with(['contact', 'whatsappAccount'])->find($this->conversationId);

        if (! $message || ! $conversation) {
            return;
        }

        if (! $conversation->isWithinWindow()) {
            Log::info('WA window expired, skipping free-form send', ['conversation' => $this->conversationId]);
            $message->update(['delivery_status' => 'failed']);

            return;
        }

        $account = $conversation->whatsappAccount;
        $contact = $conversation->contact;

        if (! $account || ! $contact) {
            return;
        }

        $wa = WhatsAppService::for($account);

        $waMessageId = $wa->sendTextMessage($contact->phone, $message->body);

        if ($waMessageId) {
            $message->update([
                'wa_message_id' => $waMessageId,
                'delivery_status' => 'sent',
            ]);

            if (! $conversation->first_reply_at) {
                $conversation->update(['first_reply_at' => now()]);
            }
        } else {
            $message->update(['delivery_status' => 'failed']);
        }
    }
}
