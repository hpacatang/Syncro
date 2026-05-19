<?php

namespace App\Http\Controllers;

use App\Support\NotificationTargetUrl;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('main.notifications', [
            'notifications' => $notifications,
        ]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function recent(Request $request)
    {
        $items = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function (DatabaseNotification $n) use ($request) {
                $data = $n->data;

                return [
                    'id' => $n->id,
                    'read' => $n->read_at !== null,
                    'created_at' => $n->created_at?->toIso8601String(),
                    'title' => $data['title'] ?? class_basename($n->type),
                    'message' => $data['message'] ?? '',
                    'url' => NotificationTargetUrl::resolve($data, $request->user()),
                ];
            });

        return response()->json(['notifications' => $items]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }
}
