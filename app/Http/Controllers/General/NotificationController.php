<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = NotificationService::getUserNotifications($user, 50);

        // Mark all as read when viewing the page
        NotificationService::markAllAsRead($user);

        return view('general.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, $notificationId)
    {
        $user = Auth::user();
        NotificationService::markAsRead($user, $notificationId);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function clearAll(Request $request)
    {
        $user = Auth::user();
        NotificationService::markAllAsRead($user);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}