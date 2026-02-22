<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'data' => $n->data,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        // Mark all as read when visiting the page
        $user->unreadNotifications->markAsRead();

        return Inertia::render('notifications/Index', [
            'notifications' => $notifications,
        ]);
    }
}
