<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPollController extends Controller
{
    /**
     * Handle the incoming poll request.
     * Returns lightweight JSON: unread count + recent notifications.
     * Designed to be called every 2-3 seconds by Alpine.js.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['unread_count' => 0, 'notifications' => []]);
        }

        $unreadCount = DatabaseNotification::query()
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', $user->getMorphClass())
            ->whereNull('read_at')
            ->count();

        $recent = DatabaseNotification::query()
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', $user->getMorphClass())
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (DatabaseNotification $n): array => [
                'id' => $n->id,
                'title' => $this->extractTitle($n),
                'body' => $this->extractBody($n),
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at?->diffForHumans(),
                'created_at_raw' => $n->created_at?->toIso8601String(),
            ]);

        /** @var DatabaseNotification|null $latest */
        $latest = DatabaseNotification::query()
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', $user->getMorphClass())
            ->latest()
            ->first();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $recent,
            'latest_created_at' => $latest?->created_at?->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = DatabaseNotification::query()
            ->where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', $request->user()->getMorphClass())
            ->whereKey($id)
            ->first();

        if ($notification) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        DatabaseNotification::query()
            ->where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', $request->user()->getMorphClass())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function extractTitle(DatabaseNotification $notification): string
    {
        $data = $notification->data;

        return is_array($data) ? ($data['title'] ?? '') : '';
    }

    private function extractBody(DatabaseNotification $notification): string
    {
        $data = $notification->data;

        return is_array($data) ? ($data['body'] ?? '') : '';
    }
}
