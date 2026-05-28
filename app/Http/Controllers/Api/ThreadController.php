<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageCreated;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ThreadController extends Controller
{
    public function index(Room $room, Message $message): AnonymousResourceCollection
    {
        $replies = $message->replies()
            ->with('user', 'reactions')
            ->oldest()
            ->paginate(50);

        return MessageResource::collection($replies);
    }

    public function store(Request $request, Room $room, Message $message): MessageResource
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $reply = Message::create([
            'body' => $data['body'],
            'type' => 'text',
            'user_id' => $request->user()->id,
            'room_id' => $room->id,
            'workspace_id' => $message->workspace_id,
            'thread_id' => $message->id,
            'is_thread_reply' => true,
        ]);

        $reply->load('user');

        broadcast(new MessageCreated($reply))->toOthers();

        return new MessageResource($reply);
    }
}
