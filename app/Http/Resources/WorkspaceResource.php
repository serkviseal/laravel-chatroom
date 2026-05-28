<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'avatar_url' => $this->avatarUrl(),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'members_count' => $this->when(isset($this->members_count), $this->members_count),
            'storage_quota_mb' => $this->storage_quota_mb,
            'storage_used_mb' => $this->storageUsedMb(),
            'role' => $this->when(
                $request->user(),
                fn () => $this->userRole($request->user())
            ),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
