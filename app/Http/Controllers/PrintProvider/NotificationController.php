<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->userNotifications()
            ->latest()
            ->paginate(12);

        return view('printProvider.notifications', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->userNotifications()->where('is_read', false)->count(),
        ]);
    }

    /**
     * What the bell shows, asked for every little while by the open page, so a new notification appears without a reload:
     * the unread count, the newest unread id (the page tells "new since last time" by it) and the menu's inside.
     */
    public function feed(Request $request): JsonResponse
    {
        $unread = $request->user()->userNotifications()->where('is_read', false);
        $count = (clone $unread)->count();
        $latest = $unread->latest()->latest('id')->limit(5)->get();

        return response()->json([
            'unread' => $count,
            'latest_id' => $latest->max('id'),
            'newest' => $latest->first()?->only(['id', 'title']),
            'html' => view('partials.notification-panel', [
                'items' => $latest,
                'readRoute' => 'print-provider.notifications.read',
                'allUrl' => route('print-provider.notifications'),
                'unread' => $count,
                'listId' => 'notificationList',
            ])->render(),
        ])->header('Cache-Control', 'no-store');
    }

    public function read(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }

        return $notification->link && str_starts_with($notification->link, url('/'))
            ? redirect()->to($notification->link)
            : redirect()->route('print-provider.notifications');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()
            ->userNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return redirect()->route('print-provider.notifications');
    }
}
