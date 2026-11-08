<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user
            ->notifications()
            ->latest()
            ->get();

        return NotificationResource::collection($notifications);
    }

    public function unread()
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user
            ->unreadNotifications
            ->sortByDesc('created_at')
            ->values();

        return NotificationResource::collection($notifications);
    }

    public function readAll()
    {
        /** @var User $user */
        $user = Auth::user();

        $user->unreadNotifications->markAsRead();

        return response()->noContent();
    }

    public function markAsRead(DatabaseNotification $notification)
    {
        Gate::authorize('markAsRead', $notification);

        $notification->markAsRead();

        return response()->noContent();
    }
}
