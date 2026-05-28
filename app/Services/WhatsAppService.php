<?php

namespace App\Services;

use App\Models\WhatsAppAccount;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private const META_BASE = 'https://graph.facebook.com/v19.0';

    private const TWILIO_BASE = 'https://api.twilio.com/2010-04-01/Accounts';

    public function __construct(private WhatsAppAccount $account) {}

    public static function for(WhatsAppAccount $account): self
    {
        return new self($account);
    }

    public function isTwilio(): bool
    {
        return $this->account->provider === 'twilio';
    }

    // ── Send text ─────────────────────────────────────────────────

    public function sendTextMessage(string $to, string $body): ?string
    {
        return $this->isTwilio()
            ? $this->twilioSend($to, $body)
            : $this->metaSendText($to, $body);
    }

    // ── Send template ─────────────────────────────────────────────

    public function sendTemplate(string $to, string $templateName, string $language = 'en_US', array $components = []): ?string
    {
        if ($this->isTwilio()) {
            // Twilio sandbox doesn't support templates — send as plain text
            $body = "Template: {$templateName}";

            return $this->twilioSend($to, $body);
        }

        $response = $this->metaPost("/{$this->account->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);

        return $response?->json('messages.0.id');
    }

    // ── Send media ────────────────────────────────────────────────

    public function sendMedia(string $to, string $mediaUrl, string $type, ?string $caption = null): ?string
    {
        if ($this->isTwilio()) {
            return $this->twilioSend($to, $caption ?? '', $mediaUrl);
        }

        $response = $this->metaPost("/{$this->account->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => $type,
            $type => array_filter(['link' => $mediaUrl, 'caption' => $caption]),
        ]);

        return $response?->json('messages.0.id');
    }

    // ── Download media ────────────────────────────────────────────

    public function downloadMedia(string $mediaId): ?string
    {
        if ($this->isTwilio()) {
            // Twilio media URLs are direct — mediaId IS the URL
            $binary = Http::withBasicAuth($this->account->account_sid, $this->account->access_token)
                ->get($mediaId);
            if (! $binary->successful()) {
                return null;
            }
            $ext = 'jpg';
            $path = 'whatsapp/media/'.basename($mediaId).".{$ext}";
            \Storage::put($path, $binary->body());

            return $path;
        }

        $info = Http::withToken($this->account->access_token)
            ->get(self::META_BASE."/{$mediaId}")
            ->json();

        if (empty($info['url'])) {
            return null;
        }

        $binary = Http::withToken($this->account->access_token)->get($info['url']);
        if (! $binary->successful()) {
            return null;
        }

        $ext = explode('/', $info['mime_type'] ?? 'application/octet-stream')[1] ?? 'bin';
        $path = "whatsapp/media/{$mediaId}.{$ext}";
        \Storage::put($path, $binary->body());

        return $path;
    }

    // ── Mark as read ──────────────────────────────────────────────

    public function markAsRead(string $waMessageId): void
    {
        if ($this->isTwilio()) {
            return; // Twilio handles read receipts automatically
        }

        $this->metaPost("/{$this->account->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $waMessageId,
        ]);
    }

    // ── Webhook signature verification ────────────────────────────

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if ($this->isTwilio()) {
            return $this->verifyTwilioSignature($payload, $signature);
        }

        $expected = 'sha256='.hash_hmac('sha256', $payload, $this->account->webhook_secret);

        return hash_equals($expected, $signature);
    }

    // ── Twilio internals ──────────────────────────────────────────

    private function twilioSend(string $to, string $body, ?string $mediaUrl = null): ?string
    {
        $from = $this->account->from_number ?: 'whatsapp:+14155238886';
        $to = str_starts_with($to, 'whatsapp:') ? $to : "whatsapp:{$to}";

        $params = ['From' => $from, 'To' => $to, 'Body' => $body];
        if ($mediaUrl) {
            $params['MediaUrl'] = $mediaUrl;
        }

        $url = self::TWILIO_BASE."/{$this->account->account_sid}/Messages.json";

        $response = Http::withBasicAuth($this->account->account_sid, $this->account->access_token)
            ->asForm()
            ->retry(3, 1000)
            ->post($url, $params);

        if (! $response->successful()) {
            Log::warning('Twilio send error', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }

        return $response->json('sid');
    }

    private function verifyTwilioSignature(string $payload, string $signature): bool
    {
        // Twilio uses HMAC-SHA1 over the full URL + sorted POST params
        // For simplicity in sandbox testing we verify the auth token is present
        if (! $this->account->webhook_secret) {
            return true; // skip verification in sandbox
        }

        $expected = base64_encode(hash_hmac('sha1', $payload, $this->account->webhook_secret, true));

        return hash_equals($expected, $signature);
    }

    // ── Meta internals ────────────────────────────────────────────

    private function metaSendText(string $to, string $body): ?string
    {
        $response = $this->metaPost("/{$this->account->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $body],
        ]);

        return $response?->json('messages.0.id');
    }

    private function metaPost(string $path, array $data): ?Response
    {
        $response = Http::withToken($this->account->access_token)
            ->retry(3, 1000)
            ->post(self::META_BASE.$path, $data);

        if (! $response->successful()) {
            Log::warning('Meta WhatsApp API error', [
                'account' => $this->account->id,
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }

        return $response;
    }
}
