<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Room;
use App\Models\StoredFile;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'type' => ['nullable', 'in:messages,channels,files'],
        ]);

        $q = $request->input('q');
        $type = $request->input('type');

        $results = [];

        if (! $type || $type === 'messages') {
            $messages = Message::search($q)
                ->where('workspace_id', $workspace->id)
                ->take(20)
                ->get()
                ->load('user', 'room');

            foreach ($messages as $m) {
                $results[] = [
                    'type' => 'message',
                    'id' => $m->id,
                    'body' => $m->body,
                    'channel' => $m->room?->name,
                    'channel_id' => $m->room_id,
                    'user' => $m->user?->name,
                    'created_at' => $m->created_at?->toISOString(),
                ];
            }
        }

        if (! $type || $type === 'channels') {
            $channels = Room::search($q)
                ->where('workspace_id', $workspace->id)
                ->take(10)
                ->get();

            foreach ($channels as $c) {
                $results[] = [
                    'type' => 'channel',
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'channel_type' => $c->type,
                ];
            }
        }

        if (! $type || $type === 'files') {
            $files = StoredFile::search($q)
                ->where('workspace_id', $workspace->id)
                ->take(10)
                ->get()
                ->load('uploader');

            foreach ($files as $f) {
                $results[] = [
                    'type' => 'file',
                    'id' => $f->id,
                    'filename' => $f->filename,
                    'mime_type' => $f->mime_type,
                    'url' => $f->url(),
                    'thumbnail_url' => $f->thumbnailUrl(),
                    'uploader' => $f->uploader?->name,
                    'created_at' => $f->created_at?->toISOString(),
                ];
            }
        }

        return response()->json(['data' => $results, 'query' => $q]);
    }
}
