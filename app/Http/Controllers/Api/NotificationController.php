<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'total_pages' => $notifications->lastPage(),
                'has_more' => $notifications->hasMorePages(),
            ],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    public function markAsRead(int $id): JsonResponse
    {
        $notification = Notification::where('user_id', request()->user()->id)
            ->findOrFail($id);

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Все уведомления отмечены как прочитанные'],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()
            ->notifications()
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
            'meta' => ['timestamp' => now()->toISOString()],
        ]);
    }
}
