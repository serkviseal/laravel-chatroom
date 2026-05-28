<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageCreated;
use App\Events\MessageReacted;
use App\Events\MessageUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use App\Http\Resources\MessageResource;
use App\Jobs\SendMentionNotification;
use App\Models\Message;
use App\Models\Room;
use App\Services\MessageParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    public function __construct(private readonly MessageParser $parser) {}

    public function index(Room $room): AnonymousResourceCollection
    {
        $messages = $room->messages()
            ->whereNull('thread_id')
            ->with('user', 'reactions')
            ->latest()
            ->paginate(50);

        return MessageResource::collection($messages);
    }

    public function store(StoreMessageRequest $request): MessageResource
    {
        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('attachments', 'public');
            $data['type'] = str_starts_with($request->file('attachment')->getMimeType(), 'image/') ? 'image' : 'file';
        }

        $message = Message::create([
            'body' => $data['body'] ?? null,
            'type' => $data['type'] ?? 'text',
            'attachment_path' => $data['attachment_path'] ?? null,
            'user_id' => $request->user()->id,
            'room_id' => $data['room_id'],
        ]);

        $message->load('user');

        broadcast(new MessageCreated($message))->toOthers();

        // Dispatch mention notifications
        if ($message->body) {
            foreach ($this->parser->extractMentions($message->body) as $name) {
                dispatch(new SendMentionNotification($message, $name));
            }
        }

        return new MessageResource($message);
    }

    public function update(UpdateMessageRequest $request, Message $message): MessageResource
    {
        $message->update([
            'body' => $request->validated('body'),
            'edited_at' => now(),
        ]);

        broadcast(new MessageUpdated($message->fresh('user')))->toOthers();

        return new MessageResource($message->load('user'));
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $message->delete();

        broadcast(new MessageUpdated($message))->toOthers();

        return response()->json(['message' => 'Deleted']);
    }

    public function react(Request $request, Message $message): MessageResource
    {
        $request->validate(['emoji' => ['required', 'string', 'max:10']]);

        $existing = $message->reactions()
            ->where('user_id', $request->user()->id)
            ->where('emoji', $request->emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $message->reactions()->create([
                'user_id' => $request->user()->id,
                'emoji' => $request->emoji,
            ]);
        }

        $message->load('user', 'reactions');

        broadcast(new MessageReacted($message))->toOthers();

        return new MessageResource($message);
    }

    public function pin(Request $request, Message $message): MessageResource
    {
        $this->authorize('pin', $message);
        $message->update(['is_pinned' => ! $message->is_pinned]);
        broadcast(new MessageUpdated($message->fresh('user')))->toOthers();

        return new MessageResource($message->load('user'));
    }
}
