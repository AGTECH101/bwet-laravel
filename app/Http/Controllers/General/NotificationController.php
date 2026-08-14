<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $readStatuses = NotificationService::getUserNotifications(auth()->user());
        return view('general.notifications.index', compact('readStatuses'));
    }

    public function markRead(Request $request, int $notificationId)
    {
        $success = NotificationService::markAsRead(auth()->user(), $notificationId);
        return response()->json(['success' => $success]);
    }

    public function clearAll()
    {
        $user = auth()->user();
        $user->notificationReadStatuses()->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now()
        ]);
        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}