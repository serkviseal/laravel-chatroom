<?php

namespace App\Http\Controllers;

use App\Events\RoomJoined;
use App\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class RoomsController extends Controller
{
    /**
     * Display a listing of the chat rooms.
     *
     * @return Response
     */
    public function index()
    {
        $rooms = Room::with('users')->paginate();

        return view('rooms.index', compact('rooms'));
    }

    /**
     * Show the form for creating a chat room.
     *
     * @return Response
     */
    public function create()
    {
        $room = app(Room::class);

        return view('rooms.create', compact('room'));
    }

    /**
     * Store a newly created room in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);

        try {
            $room = Room::create([
                'name' => $request->get('name'),
                'description' => $request->get('description'),
            ]);

            $request->user()->addRoom($room);
        } catch (Exception $e) {
            Log::error('Exception while creating a chatroom', [
                'file' => $e->getFile(),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            return back()->withInput();
        }

        return redirect()->route('rooms.index');
    }

    /**
     * Show room with messages
     *
     * @param  mixed  $room
     */
    public function show(Room $room)
    {
        $room = $room->load('messages');

        return view('rooms.show', compact('room'));
    }

    /**
     * Allow user to join chat room
     */
    public function join(Room $room, Request $request)
    {
        try {
            $room->join($request->user());

            event(new RoomJoined($request->user(), $room));
        } catch (Exception $e) {
            Log::error('Exception while joining a chat room', [
                'file' => $e->getFile(),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            return back();
        }

        return redirect()->route('rooms.show', ['room' => $room->id]);
    }
}
