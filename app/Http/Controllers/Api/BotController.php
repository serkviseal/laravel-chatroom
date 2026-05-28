<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageCreated;
use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Message;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    /** List bots for a workspace */
    public function index(Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);

        return response()->json($workspace->bots()->get());
    }

    /** Register a new bot */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'outgoing_webhook_url' => ['nullable', 'url'],
            'subscribed_events' => ['nullable', 'array'],
            'channel_ids' => ['nullable', 'array'],
            'provider' => ['nullable', 'string', 'in:whatsapp,slack,teams,custom'],
            'provider_config' => ['nullable', 'array'],
        ]);

        $bot = $workspace->bots()->create([
            ...$data,
            'auth_token' => Bot::generateToken(),
        ]);

        return response()->json($bot, 201);
    }

    /** Regenerate auth token */
    public function regenerateToken(Workspace $workspace, Bot $bot): JsonResponse
    {
        $this->authorize('update', $workspace);
        $bot->update(['auth_token' => Bot::generateToken()]);

        return response()->json(['auth_token' => $bot->auth_token]);
    }

    /** Destroy a bot */
    public function destroy(Workspace $workspace, Bot $bot): JsonResponse
    {
        $this->authorize('update', $workspace);
        $bot->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Incoming webhook — external services POST messages here.
     *
     * POST /api/v1/webhooks/incoming/{token}
     *
     * Body: { "channel_id": 1, "body": "Hello from WhatsApp" }
     *       OR WhatsApp Cloud API format (auto-detected via provider)
     *
     * The bot's auth_token is used as the URL token (no user session needed).
     */
    public function incoming(Request $request, string $token): JsonResponse
    {
        $bot = Bot::where('auth_token', $token)->where('is_active', true)->firstOrFail();

        $payload = $this->normalisePayload($bot, $request->all());

        if (! $payload) {
            return response()->json(['error' => 'Unrecognised payload format'], 422);
        }

        if (! $bot->allowsChannel($payload['channel_id'])) {
            return response()->json(['error' => 'Channel not permitted for this bot'], 403);
        }

        // Create the message attributed to the bot (no real user)
        $message = Message::create([
            'body' => $payload['body'],
            'type' => 'text',
            'user_id' => $bot->workspace->owner_id, // bot messages attributed to workspace owner for now
            'room_id' => $payload['channel_id'],
        ]);

        broadcast(new MessageCreated($message));

        return response()->json(['ok' => true, 'message_id' => $message->id]);
    }

    // ── Outgoing delivery (called from MessageCreated listener) ──────────

    public static function dispatchToSubscribedBots(Message $message): void
    {
        $bots = Bot::where('workspace_id', $message->room->workspace_id ?? null)
            ->where('is_active', true)
            ->whereNotNull('outgoing_webhook_url')
            ->get();

        foreach ($bots as $bot) {
            if (! $bot->subscribedTo('message.created')) {
                continue;
            }
            if (! $bot->allowsChannel($message->room_id)) {
                continue;
            }

            self::postToWebhook($bot, $message);
        }
    }

    private static function postToWebhook(Bot $bot, Message $message): void
    {
        try {
            Http::timeout(5)->post($bot->outgoing_webhook_url, [
                'event' => 'message.created',
                'channel' => ['id' => $message->room_id, 'name' => $message->room?->name],
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'sender' => $message->user?->name,
                    'created_at' => $message->created_at->toISOString(),
                ],
                'workspace' => ['id' => $message->room?->workspace_id],
            ]);
        } catch (\Throwable $e) {
            Log::warning("Webhook delivery failed for bot {$bot->id}: {$e->getMessage()}");
        }
    }

    // ── Payload normalisation ─────────────────────────────────────────────

    private function normalisePayload(Bot $bot, array $raw): ?array
    {
        // Generic format
        if (isset($raw['body'], $raw['channel_id'])) {
            return ['body' => $raw['body'], 'channel_id' => (int) $raw['channel_id']];
        }

        // WhatsApp Cloud API format
        if ($bot->provider === 'whatsapp' && isset($raw['entry'])) {
            $text = data_get($raw, 'entry.0.changes.0.value.messages.0.text.body');
            $channelId = data_get($bot->provider_config, 'default_channel_id');
            if ($text && $channelId) {
                return ['body' => $text, 'channel_id' => (int) $channelId];
            }
        }

        return null;
    }
}
