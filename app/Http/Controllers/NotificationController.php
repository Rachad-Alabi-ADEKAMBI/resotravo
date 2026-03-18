<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * GET /notifications
     * Retourne les 30 dernières notifs + compteur non-lus.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->take(30)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'event'      => $n->data['event']  ?? null,
                'title'      => $n->data['title']  ?? 'Notification',
                'body'       => $n->data['body']   ?? '',
                'icon'       => $n->data['icon']   ?? 'bell',
                'url'        => $n->data['url']    ?? null,
                'mission_id' => $n->data['mission_id'] ?? null,
                'read'       => ! is_null($n->read_at),
                'created_at' => $n->created_at->toISOString(),
                'ago'        => $n->created_at->locale('fr')->diffForHumans(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * PATCH /notifications/{id}/read
     */
    public function markRead(string $id): JsonResponse
    {
        Auth::user()
            ->notifications()
            ->findOrFail($id)
            ->markAsRead();

        return response()->json(['message' => "Marquée comme lue."]);
    }

    /**
     * PATCH /notifications/read-all
     */
    public function markAllRead(): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['message' => "Toutes les notifications ont été lues."]);
    }
}