<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppWebhook;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    // GET — Meta hub verification challenge
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

    // POST — incoming events from Meta
    public function receive(Request $request, int $accountId): Response
    {
        $account = WhatsAppAccount::find($accountId);

        if (!$account) {
            return response('Not Found', 404);
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $payload   = $request->getContent();

        if ($account->webhook_secret && !$this->verifySignature($payload, $signature, $account->webhook_secret)) {
            return response('Forbidden', 403);
        }

        ProcessWhatsAppWebhook::dispatch($accountId, $request->all())
            ->onQueue('whatsapp-inbound');

        return response('OK', 200);
    }

    private function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
