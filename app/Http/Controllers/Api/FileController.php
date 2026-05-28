<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FileResource;
use App\Jobs\DeleteFileJob;
use App\Models\Room;
use App\Models\StoredFile;
use App\Models\Workspace;
use App\Services\WorkspaceStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function __construct(private readonly WorkspaceStorageService $storage) {}

    public function index(Request $request, Workspace $workspace): AnonymousResourceCollection
    {
        $this->authorize('view', $workspace);

        $query = StoredFile::where('workspace_id', $workspace->id)
            ->with('uploader')
            ->latest();

        if ($type = $request->query('type')) {
            match ($type) {
                'images' => $query->where('mime_type', 'like', 'image/%'),
                'docs' => $query->where(function ($q) {
                    $q->where('mime_type', 'like', 'application/%')
                        ->orWhere('mime_type', 'like', 'text/%');
                }),
                'videos' => $query->where('mime_type', 'like', 'video/%'),
                default => null,
            };
        }

        if ($channelId = $request->query('channel_id')) {
            $query->where('channel_id', $channelId);
        }

        return FileResource::collection($query->paginate(50));
    }

    public function upload(Request $request, Workspace $workspace): FileResource
    {
        $this->authorize('view', $workspace);

        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'channel_id' => ['nullable', 'exists:rooms,id'],
        ]);

        $file = $request->file('file');
        $this->storage->checkQuota($workspace, $file->getSize());

        $disk = config('filesystems.default');
        $path = $file->store("workspaces/{$workspace->id}/files", $disk);

        $thumbnailPath = null;
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $thumbnailPath = $this->generateThumbnail($file, $workspace->id, $disk);
        }

        $record = StoredFile::create([
            'workspace_id' => $workspace->id,
            'uploader_id' => $request->user()->id,
            'channel_id' => $request->input('channel_id'),
            'filename' => $file->getClientOriginalName(),
            'disk_path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'is_public' => false,
        ]);

        return new FileResource($record->load('uploader'));
    }

    public function share(Request $request, StoredFile $file): JsonResponse
    {
        $this->authorize('view', $file);
        return response()->json($file->shareLink());
    }

    public function destroy(Request $request, StoredFile $file): JsonResponse
    {
        $this->authorize('delete', $file);

        dispatch(new DeleteFileJob($file->disk_path, $file->thumbnail_path));
        $file->delete();

        return response()->json(['message' => 'File deleted']);
    }

    public function shared(string $token): mixed
    {
        $file = StoredFile::where('shared_token', $token)
            ->where('shared_expires_at', '>', now())
            ->firstOrFail();

        $disk = config('filesystems.default');
        return Storage::disk($disk)->download($file->disk_path, $file->filename);
    }

    private function generateThumbnail(\Illuminate\Http\UploadedFile $file, int $workspaceId, string $disk): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }
        try {
            $img = imagecreatefromstring(file_get_contents($file->getRealPath()));
            if (! $img) {
                return null;
            }
            $w = imagesx($img);
            $h = imagesy($img);
            $size = min($w, $h, 150);
            $thumb = imagecreatetruecolor(150, 150);
            imagecopyresampled($thumb, $img, 0, 0, 0, 0, 150, 150, $w, $h);
            $tmpPath = sys_get_temp_dir().'/'.Str::random(16).'.jpg';
            imagejpeg($thumb, $tmpPath, 80);
            imagedestroy($img);
            imagedestroy($thumb);
            $stored = Storage::disk($disk)->putFile("workspaces/{$workspaceId}/thumbnails", new \Illuminate\Http\File($tmpPath));
            @unlink($tmpPath);
            return $stored ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
