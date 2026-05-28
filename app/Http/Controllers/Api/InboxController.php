<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppConversation;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $query = WhatsAppConversation::with(['contact', 'assignedAgent', 'room'])
            ->where('workspace_id', $workspace->id);

        // Status filter
        $status = $request->query('status', 'open');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Assignment filter
        $assigned = $request->query('assigned_to');
        if ($assigned === 'me') {
            $query->where('assigned_agent_id', auth()->id());
        } elseif ($assigned === 'unassigned') {
            $query->whereNull('assigned_agent_id');
        }

        $conversations = $query->latest()->paginate(25);

        return response()->json($conversations);
    }

    public function assign(Request $request, Workspace $workspace, WhatsAppConversation $conversation): JsonResponse
    {
        $this->authorize('manage', $workspace);

        $data = $request->validate([
            'agent_id' => 'nullable|exists:users,id',
            'bot_id' => 'nullable|exists:bots,id',
        ]);

        $conversation->update([
            'assigned_agent_id' => $data['agent_id'] ?? null,
            'assigned_bot_id' => $data['bot_id'] ?? null,
        ]);

        return response()->json($conversation->load(['assignedAgent', 'assignedBot']));
    }

    public function updateStatus(Request $request, Workspace $workspace, WhatsAppConversation $conversation): JsonResponse
    {
        $this->authorize('manage', $workspace);

        $data = $request->validate([
            'status' => 'required|in:open,pending,snoozed,resolved,spam',
            'snoozed_until' => 'required_if:status,snoozed|nullable|date',
        ]);

        $updates = ['status' => $data['status']];

        if ($data['status'] === 'resolved') {
            $updates['resolved_at'] = now();
        } elseif ($data['status'] === 'snoozed') {
            $updates['snoozed_until'] = $data['snoozed_until'];
        }

        $conversation->update($updates);

        return response()->json($conversation);
    }
}
