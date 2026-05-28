<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(30);

        return response()->json([
            'data' => $notifications->items(),
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'next_page_url' => $notifications->nextPageUrl(),
        ]);
    }

    public function count(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $id = $request->input('id');
        if ($id) {
            $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        } else {
            $request->user()->unreadNotifications->markAsRead();
        }

        return response()->json(['message' => 'Marked as read']);
    }
}
