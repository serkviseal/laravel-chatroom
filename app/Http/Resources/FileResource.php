<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'size_human' => $this->humanSize(),
            'url' => $this->url(),
            'thumbnail_url' => $this->thumbnailUrl(),
            'is_image' => $this->isImage(),
            'uploader' => new UserResource($this->whenLoaded('uploader')),
            'channel_id' => $this->channel_id,
            'workspace_id' => $this->workspace_id,
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    private function humanSize(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        return round($bytes / 1024, 1).' KB';
    }
}
