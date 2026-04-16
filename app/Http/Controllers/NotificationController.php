<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->take(40)
            ->get();

        $unreadCount = $rows->whereNull('read_at')->count();

        $notifications = $rows->map(fn ($n) => [
            'id'         => $n->id,
            'type'       => $n->type,
            'title'      => $n->title,
            'body'       => $n->body,
            'data'       => $n->data,
            'read'       => ! is_null($n->read_at),
            'created_at' => $n->created_at->diffForHumans(),
        ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);
        $notification->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
