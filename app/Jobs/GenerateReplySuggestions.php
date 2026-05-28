<?php

namespace App\Jobs;

use App\Events\AiSuggestionsReady;
use App\Models\Message;
use App\Models\WhatsAppConversation;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateReplySuggestions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(
        private WhatsAppConversation $conversation,
    ) {}

    public function handle(): void
    {
        $apiKey = config('services.anthropic.key');
        if (! $apiKey) {
            return;
        }

        $history = Message::where('room_id', $this->conversation->room_id)
            ->latest()
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn ($m) => [
                'role' => $m->origin === 'whatsapp' ? 'user' : 'assistant',
                'content' => $m->body,
            ])
            ->values()
            ->toArray();

        $workspace = $this->conversation->workspace ?? new Workspace;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 500,
            'system' => "You are a helpful customer support agent for {$workspace->name}. "
                .'Draft 3 short, professional reply options. Return JSON array: [{"text": "..."}, ...]. No extra text.',
            'messages' => $history,
        ]);

        if (! $response->successful()) {
            Log::warning('AI suggestion failed', ['status' => $response->status()]);

            return;
        }

        $content = $response->json('content.0.text');
        $suggestions = json_decode($content, true);

        if (! is_array($suggestions)) {
            return;
        }

        // Broadcast suggestions to agents watching this conversation
        broadcast(new AiSuggestionsReady(
            $this->conversation->id,
            $suggestions
        ))->toOthers();
    }
}
