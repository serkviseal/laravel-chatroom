<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotRule;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotRuleController extends Controller
{
    public function index(Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        return response()->json(
            $workspace->botRules()->orderBy('priority')->get()
        );
    }

    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('manage', $workspace);

        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'bot_id'        => 'nullable|exists:bots,id',
            'trigger_type'  => 'required|in:keyword,regex,intent,always,outside_hours,first_contact,unassigned_timeout',
            'trigger_value' => 'nullable|string|max:500',
            'action_type'   => 'required|in:reply,assign_agent,assign_bot,add_label,close,send_template,escalate_ai',
            'action_value'  => 'nullable|array',
            'priority'      => 'nullable|integer|min:1|max:9999',
            'is_active'     => 'boolean',
            'stop_on_match' => 'boolean',
        ]);

        $data['workspace_id'] = $workspace->id;
        $data['priority'] ??= ($workspace->botRules()->max('priority') ?? 0) + 10;

        return response()->json(BotRule::create($data), 201);
    }

    public function update(Request $request, Workspace $workspace, BotRule $rule): JsonResponse
    {
        $this->authorize('manage', $workspace);

        $data = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'trigger_type'  => 'sometimes|in:keyword,regex,intent,always,outside_hours,first_contact,unassigned_timeout',
            'trigger_value' => 'nullable|string|max:500',
            'action_type'   => 'sometimes|in:reply,assign_agent,assign_bot,add_label,close,send_template,escalate_ai',
            'action_value'  => 'nullable|array',
            'priority'      => 'sometimes|integer|min:1|max:9999',
            'is_active'     => 'sometimes|boolean',
            'stop_on_match' => 'sometimes|boolean',
        ]);

        $rule->update($data);

        return response()->json($rule);
    }

    public function destroy(Workspace $workspace, BotRule $rule): JsonResponse
    {
        $this->authorize('manage', $workspace);
        $rule->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('manage', $workspace);

        $data = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:bot_rules,id',
        ]);

        foreach ($data['order'] as $priority => $id) {
            BotRule::where('id', $id)
                ->where('workspace_id', $workspace->id)
                ->update(['priority' => ($priority + 1) * 10]);
        }

        return response()->json(['ok' => true]);
    }
}
