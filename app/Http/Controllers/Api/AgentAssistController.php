<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\WhatsAppConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AgentAssistController extends Controller
{
    public function suggestReply(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation->workspace);

        $apiKey = config('services.anthropic.key');
        if (! $apiKey) {
            return response()->json(['error' => 'AI not configured'], 503);
        }

        $history = Message::where('room_id', $conversation->room_id)
            ->latest()
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn ($m) => [
                'role' => $m->origin === 'whatsapp' ? 'user' : 'assistant',
                'content' => $m->body,
            ])
            ->values()
            ->all();

        if (empty($history)) {
            return response()->json(['suggestions' => []]);
        }

        $workspace = $conversation->workspace;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 600,
            'system' => "You are a helpful customer support agent for {$workspace->name}. "
                .'Generate 3 concise reply options. Return ONLY a JSON array: [{"text":"..."},{"text":"..."},{"text":"..."}]',
            'messages' => $history,
        ]);

        if (! $response->successful()) {
            return response()->json(['error' => 'AI request failed'], 502);
        }

        $raw = $response->json('content.0.text', '[]');
        $suggestions = json_decode($raw, true);

        if (! is_array($suggestions)) {
            $suggestions = [['text' => $raw]];
        }

        return response()->json(['suggestions' => $suggestions]);
    }
}
