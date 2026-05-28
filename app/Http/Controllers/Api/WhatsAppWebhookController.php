<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppWebhook;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    // GET — Meta hub verification challenge (not used by Twilio)
    public function verify(Request $request, int $accountId): Response
    {
        $account = WhatsAppAccount::findOrFail($accountId);

        $mode      = $request->query('hub_mode');
        $challenge = $request->query('hub_challenge');
        $token     = $request->query('hub_verify_token');

        if ($mode === 'subscribe' && $token === $account->verify_token) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // POST — incoming events (Meta JSON or Twilio form-POST)
    public function receive(Request $request, int $accountId): Response
    {
        $account = WhatsAppAccount::find($accountId);

        if (!$account) {
            return response('Not Found', 404);
        }

        $isTwilio = $account->provider === 'twilio';

        // Signature verification
        if ($isTwilio) {
            $signature = $request->header('X-Twilio-Signature', '');
            if ($account->webhook_secret && !$this->verifyTwilioSignature($request, $signature, $account->webhook_secret)) {
                return response('Forbidden', 403);
            }
        } else {
            $signature = $request->header('X-Hub-Signature-256', '');
            if ($account->webhook_secret && !$this->verifyMetaSignature($request->getContent(), $signature, $account->webhook_secret)) {
                return response('Forbidden', 403);
            }
        }

        // Normalise payload into a common structure
        $normalised = $isTwilio
            ? $this->normaliseTwilioPayload($request)
            : $request->all();

        if ($normalised) {
            ProcessWhatsAppWebhook::dispatch($accountId, $normalised)
                ->onQueue('whatsapp-inbound');
        }

        // Twilio expects a TwiML response (or empty 200)
        return $isTwilio
            ? response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
                ->header('Content-Type', 'text/xml')
            : response('OK', 200);
    }

    // ── Twilio form-POST → Meta-compatible payload ────────────────

    private function normaliseTwilioPayload(Request $request): ?array
    {
        $type = $request->input('SmsStatus') ?? null;

        // Status callback (sent/delivered/read/failed)
        if ($type && !$request->has('Body')) {
            return [
                '_provider' => 'twilio',
                '_type'     => 'status',
                'entry'     => [[
                    'changes' => [[
                        'value' => [
                            'statuses' => [[
                                'id'     => $request->input('MessageSid'),
                                'status' => $this->mapTwilioStatus($type),
                            ]],
                        ],
                        'field' => 'messages',
                    ]],
                ]],
            ];
        }

        // Inbound message
        $from = ltrim($request->input('From', ''), 'whatsapp:');
        $body = $request->input('Body', '');

        if (!$from) {
            return null;
        }

        return [
            '_provider' => 'twilio',
            '_type'     => 'message',
            'entry'     => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'contacts'          => [[
                            'profile' => ['name' => $request->input('ProfileName', $from)],
                            'wa_id'   => $from,
                        ]],
                        'messages' => [[
                            'from'      => $from,
                            'id'        => $request->input('MessageSid', 'twilio-' . uniqid()),
                            'timestamp' => (string) now()->timestamp,
                            'type'      => $request->has('NumMedia') && (int) $request->input('NumMedia') > 0 ? 'image' : 'text',
                            'text'      => ['body' => $body],
                            // Include media URL if present
                            'image'     => $request->input('MediaUrl0') ? ['link' => $request->input('MediaUrl0'), 'caption' => $body] : null,
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];
    }

    private function mapTwilioStatus(string $status): string
    {
        return match (strtolower($status)) {
            'sent'      => 'sent',
            'delivered' => 'delivered',
            'read'      => 'read',
            'failed', 'undelivered' => 'failed',
            default     => 'sent',
        };
    }

    // ── Signature helpers ─────────────────────────────────────────

    private function verifyMetaSignature(string $payload, string $signature, string $secret): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    private function verifyTwilioSignature(Request $request, string $signature, string $authToken): bool
    {
        $url    = $request->fullUrl();
        $params = $request->post();
        ksort($params);

        $data     = $url . implode('', array_map(fn ($k, $v) => $k . $v, array_keys($params), $params));
        $expected = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        return hash_equals($expected, $signature);
    }
}
