<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\NotificationReadStatus;
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

        return redirect()->route('notifications.index')->with(
            'success',
            $success ? 'Notification marked as read.' : 'Notification was already read.'
        );
    }

    public function clearAll()
    {
        $user = auth()->user();
        $updated = NotificationReadStatus::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()->route('notifications.index')->with(
            'success',
            $updated > 0 ? 'All notifications marked as read.' : 'There were no unread notifications.'
        );
    }
}