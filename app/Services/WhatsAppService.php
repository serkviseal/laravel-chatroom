<?php

namespace App\Services;

use App\Models\WhatsAppAccount;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private const BASE_URL = 'https://graph.facebook.com/v19.0';

    public function __construct(private WhatsAppAccount $account) {}

    public static function for(WhatsAppAccount $account): static
    {
        return new static($account);
    }

    public function sendTextMessage(string $to, string $body): ?string
    {
        $response = $this->post("/{$this->account->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => $body],
        ]);

        return $response?->json('messages.0.id');
    }

    public function sendTemplate(string $to, string $templateName, string $language = 'en_US', array $components = []): ?string
    {
        $response = $this->post("/{$this->account->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $language],
                'components' => $components,
            ],
        ]);

        return $response?->json('messages.0.id');
    }

    public function sendMedia(string $to, string $mediaUrl, string $type, ?string $caption = null): ?string
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => $type,
            $type               => array_filter(['link' => $mediaUrl, 'caption' => $caption]),
        ];

        $response = $this->post("/{$this->account->phone_number_id}/messages", $payload);

        return $response?->json('messages.0.id');
    }

    public function downloadMedia(string $mediaId): ?string
    {
        $info = Http::withToken($this->account->access_token)
            ->get(self::BASE_URL . "/{$mediaId}")
            ->json();

        if (empty($info['url'])) {
            return null;
        }

        $binary = Http::withToken($this->account->access_token)->get($info['url']);

        if (!$binary->successful()) {
            return null;
        }

        $ext  = explode('/', $info['mime_type'] ?? 'application/octet-stream')[1] ?? 'bin';
        $path = "whatsapp/media/{$mediaId}.{$ext}";

        \Storage::put($path, $binary->body());

        return $path;
    }

    public function markAsRead(string $waMessageId): void
    {
        $this->post("/{$this->account->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $waMessageId,
        ]);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $this->account->webhook_secret);

        return hash_equals($expected, $signature);
    }

    private function post(string $path, array $data): ?Response
    {
        $response = Http::withToken($this->account->access_token)
            ->retry(3, 1000)
            ->post(self::BASE_URL . $path, $data);

        if (!$response->successful()) {
            Log::warning('WhatsApp API error', [
                'account' => $this->account->id,
                'path'    => $path,
                'status'  => $response->status(),
                'body'    => $response->json(),
            ]);

            return null;
        }

        return $response;
    }
}
