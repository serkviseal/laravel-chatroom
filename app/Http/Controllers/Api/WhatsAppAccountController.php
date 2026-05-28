<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\WhatsAppAccount;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Str;

class WhatsAppAccountController extends Controller
{
    public function index(Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        return response()->json(
            $workspace->whatsappAccounts()->get()->makeVisible(['access_token'])
        );
    }

    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('manage', $workspace);

        $data = $request->validate([
            'display_name'    => 'required|string|max:100',
            'phone_number_id' => 'required|string',
            'waba_id'         => 'required|string',
            'access_token'    => 'required|string',
            'webhook_secret'  => 'nullable|string',
            'business_hours'  => 'nullable|array',
            'welcome_template' => 'nullable|string',
        ]);

        $data['workspace_id'] = $workspace->id;
        $data['verify_token'] = Str::random(32);
        $data['is_active']    = true;

        $account = WhatsAppAccount::create($data);

        return response()->json([
            'account'     => $account->makeVisible(['access_token']),
            'webhook_url' => url("/api/webhooks/whatsapp/{$account->id}"),
            'verify_token' => $account->verify_token,
        ], 201);
    }

    public function update(Request $request, Workspace $workspace, WhatsAppAccount $account): JsonResponse
    {
        $this->authorize('manage', $workspace);

        $data = $request->validate([
            'display_name'     => 'sometimes|string|max:100',
            'access_token'     => 'sometimes|string',
            'webhook_secret'   => 'nullable|string',
            'business_hours'   => 'nullable|array',
            'welcome_template' => 'nullable|string',
            'is_active'        => 'sometimes|boolean',
        ]);

        $account->update($data);

        return response()->json($account->makeVisible(['access_token']));
    }

    public function destroy(Workspace $workspace, WhatsAppAccount $account): JsonResponse
    {
        $this->authorize('manage', $workspace);
        $account->delete();

        return response()->json(null, 204);
    }

    public function testConnection(Workspace $workspace, WhatsAppAccount $account): JsonResponse
    {
        $this->authorize('manage', $workspace);

        $testPhone = request()->validate(['phone' => 'required|string'])['phone'];

        $waService = WhatsAppService::for($account);
        $messageId = $waService->sendTextMessage($testPhone, '✅ WhatsApp integration test successful!');

        return response()->json([
            'success'    => (bool) $messageId,
            'message_id' => $messageId,
        ]);
    }
}
