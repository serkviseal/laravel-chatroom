<?php

namespace App\Jobs;

use App\Events\MessageCreated;
use App\Models\Message;
use App\Models\Room;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Services\BotRuleEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        private int $accountId,
        private array $payload
    ) {}

    public function handle(BotRuleEngine $engine): void
    {
        $account = WhatsAppAccount::find($this->accountId);
        if (! $account) {
            return;
        }

        $entry = $this->payload['entry'][0] ?? null;
        if (! $entry) {
            return;
        }

        foreach ($entry['changes'] ?? [] as $change) {
            $value = $change['value'] ?? [];

            $this->processStatuses($value['statuses'] ?? [], $account);
            $this->processMessages($value['messages'] ?? [], $value['contacts'] ?? [], $account, $engine);
        }
    }

    private function processMessages(array $messages, array $contacts, WhatsAppAccount $account, BotRuleEngine $engine): void
    {
        $profileMap = collect($contacts)->keyBy('wa_id');

        foreach ($messages as $msg) {
            $waId = $msg['from'];
            $profile = $profileMap[$waId] ?? [];

            $contact = WhatsAppContact::firstOrCreate(
                ['whatsapp_account_id' => $account->id, 'phone' => $waId],
                [
                    'workspace_id' => $account->workspace_id,
                    'profile_name' => $profile['profile']['name'] ?? null,
                    'display_name' => $profile['profile']['name'] ?? $waId,
                    'last_seen_at' => now(),
                ]
            );

            $contact->update(['last_seen_at' => now()]);

            $conversation = $this->findOrCreateConversation($contact, $account);
            $conversation->refreshWindow();

            $body = $this->extractBody($msg);

            if ($body === null && ($msg['type'] ?? '') === 'image') {
                DownloadWhatsAppMedia::dispatch($account->id, $msg['image']['id'] ?? '', $conversation->id);
            }

            $message = Message::create([
                'room_id' => $conversation->room_id,
                'user_id' => null,
                'body' => $body ?? '[media]',
                'origin' => 'whatsapp',
                'wa_message_id' => $msg['id'],
                'delivery_status' => 'delivered',
            ]);

            try {
                event(new MessageCreated($message));
            } catch (\Throwable $e) {
                Log::warning('Broadcast failed for WA message', ['error' => $e->getMessage()]);
            }

            EvaluateBotRules::dispatch($message->id, $conversation->id);
        }
    }

    private function processStatuses(array $statuses, WhatsAppAccount $account): void
    {
        foreach ($statuses as $status) {
            Message::where('wa_message_id', $status['id'])
                ->update(['delivery_status' => $status['status']]);
        }
    }

    private function findOrCreateConversation(WhatsAppContact $contact, WhatsAppAccount $account): WhatsAppConversation
    {
        $existing = WhatsAppConversation::where('contact_id', $contact->id)
            ->whereIn('status', ['open', 'pending'])
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $room = Room::create([
            'workspace_id' => $account->workspace_id,
            'name' => $contact->display_name,
            'type' => 'direct',
            'source' => 'whatsapp',
            'is_inbox_item' => true,
            'contact_metadata' => [
                'phone' => $contact->phone,
                'name' => $contact->display_name,
                'wa_id' => $contact->phone,
                'labels' => [],
            ],
        ]);

        return WhatsAppConversation::create([
            'workspace_id' => $account->workspace_id,
            'whatsapp_account_id' => $account->id,
            'contact_id' => $contact->id,
            'room_id' => $room->id,
            'status' => 'open',
            'conversation_type' => 'contact',
            'window_expires_at' => now()->addHours(24),
        ]);
    }

    private function extractBody(array $msg): ?string
    {
        return match ($msg['type'] ?? '') {
            'text' => $msg['text']['body'] ?? null,
            'image' => $msg['image']['caption'] ?? null,
            'video' => $msg['video']['caption'] ?? null,
            'document' => $msg['document']['filename'] ?? '[document]',
            'audio' => '[voice message]',
            'location' => sprintf('[location: %s, %s]', $msg['location']['latitude'] ?? '', $msg['location']['longitude'] ?? ''),
            default => null,
        };
    }
}
