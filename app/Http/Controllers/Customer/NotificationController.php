<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
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

        return view('customer.notifications', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->userNotifications()->where('is_read', false)->count(),
        ]);
    }

    public function read(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }

        return $notification->link && str_starts_with($notification->link, url('/'))
            ? redirect()->to($notification->link)
            : redirect()->route('customer.notifications');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()
            ->userNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return redirect()->route('customer.notifications');
    }
}
