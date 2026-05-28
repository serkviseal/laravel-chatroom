<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $workspaces = Workspace::forUser($request->user())
            ->with('owner')
            ->withCount('members')
            ->get();

        return WorkspaceResource::collection($workspaces);
    }

    public function store(Request $request): WorkspaceResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = Str::slug($data['name']);
        $base = $slug;
        $i = 1;
        while (Workspace::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        $workspace = Workspace::create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'owner_id' => $request->user()->id,
        ]);

        $workspace->members()->attach($request->user(), ['role' => 'owner', 'joined_at' => now()]);

        return new WorkspaceResource($workspace->load('owner')->loadCount('members'));
    }

    public function show(Workspace $workspace): WorkspaceResource
    {
        $this->authorize('view', $workspace);

        return new WorkspaceResource($workspace->load('owner')->loadCount('members'));
    }

    public function update(Request $request, Workspace $workspace): WorkspaceResource
    {
        $this->authorize('update', $workspace);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        $workspace->update($data);

        return new WorkspaceResource($workspace->load('owner')->loadCount('members'));
    }

    public function join(Request $request, Workspace $workspace): JsonResponse
    {
        if (! $workspace->hasMember($request->user())) {
            $workspace->members()->attach($request->user(), ['role' => 'member', 'joined_at' => now()]);
        }

        return response()->json(['message' => 'Joined workspace']);
    }

    public function leave(Request $request, Workspace $workspace): JsonResponse
    {
        if ($workspace->owner_id === $request->user()->id) {
            abort(422, 'Owner cannot leave the workspace.');
        }
        $workspace->members()->detach($request->user());

        return response()->json(['message' => 'Left workspace']);
    }

    public function members(Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $members = User::query()
            ->join('workspace_user', 'users.id', '=', 'workspace_user.user_id')
            ->where('workspace_user.workspace_id', $workspace->id)
            ->select('users.*', 'workspace_user.role', 'workspace_user.joined_at')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'avatar_url' => $u->avatar_url,
                'role' => $u->getAttribute('role'),
                'joined_at' => $u->getAttribute('joined_at'),
            ]);

        return response()->json(['data' => $members]);
    }

    public function updateMemberRole(Request $request, Workspace $workspace, int $userId): JsonResponse
    {
        $this->authorize('manageMembers', $workspace);
        $data = $request->validate(['role' => ['required', 'in:admin,member,guest']]);
        $workspace->members()->updateExistingPivot($userId, ['role' => $data['role']]);

        return response()->json(['message' => 'Role updated']);
    }

    public function removeMember(Request $request, Workspace $workspace, int $userId): JsonResponse
    {
        $this->authorize('manageMembers', $workspace);
        if ($workspace->owner_id === $userId) {
            abort(422, 'Cannot remove the workspace owner.');
        }
        $workspace->members()->detach($userId);

        return response()->json(['message' => 'Member removed']);
    }

    public function storageStats(Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        return response()->json([
            'used_mb' => $workspace->storageUsedMb(),
            'quota_mb' => $workspace->storage_quota_mb,
            'percent_used' => app(WorkspaceStorageService::class)->percentUsed($workspace),
        ]);
    }
}
