<?php

namespace App\Http\Controllers\Api;

use App\Events\RoomJoined;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $rooms = Room::with(['users', 'latestMessage.user'])
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->latest()
            ->paginate(20);

        return RoomResource::collection($rooms);
    }

    public function store(StoreRoomRequest $request): RoomResource
    {
        $room = Room::create($request->validated());
        $room->join($request->user());

        return new RoomResource($room->load('users'));
    }

    public function show(Room $room): RoomResource
    {
        return new RoomResource($room->load(['users', 'latestMessage.user']));
    }

    public function join(Room $room, Request $request): JsonResponse
    {
        $room->join($request->user());
        event(new RoomJoined($request->user(), $room));

        return response()->json(['message' => 'Joined room']);
    }

    public function leave(Room $room, Request $request): JsonResponse
    {
        $room->leave($request->user());

        return response()->json(['message' => 'Left room']);
    }
}
