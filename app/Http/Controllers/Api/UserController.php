<?php

namespace App\Http\Controllers\Api;

use App\Events\UserStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\WorkspacePreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('workspaces'));
    }

    public function update(Request $request): UserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $request->user()->update($data);

        return new UserResource($request->user()->fresh());
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'status' => ['required', 'in:online,away,dnd,offline'],
            'status_emoji' => ['nullable', 'string', 'max:10'],
            'status_text' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $pref = WorkspacePreference::updateOrCreate(
            ['user_id' => $user->id, 'workspace_id' => $data['workspace_id']],
            [
                'status' => $data['status'],
                'status_emoji' => $data['status_emoji'] ?? null,
                'status_text' => $data['status_text'] ?? null,
            ]
        );

        broadcast(new UserStatusChanged($user, $data))->toOthers();

        return response()->json(['status' => $pref->status, 'status_emoji' => $pref->status_emoji, 'status_text' => $pref->status_text]);
    }
}
