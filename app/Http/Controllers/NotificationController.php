<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * In-app уведомления (колокольчик).
 */
class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = Auth::user()
            ->notifications()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(string $id): RedirectResponse|JsonResponse
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'unread' => Auth::user()->unreadNotifications()->count()]);
        }

        $url = $notification->data['url'] ?? route('notifications.index');

        return redirect()->to($url);
    }

    public function markAllRead(): RedirectResponse|JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'unread' => 0]);
        }

        return redirect()->back()->with('success', 'Все уведомления прочитаны');
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'unread' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
