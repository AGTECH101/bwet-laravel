<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationReadStatus;
use App\Models\User;
use App\Models\Poultry\Batch;
use App\Models\ObservationReport;

class NotificationService
{
    /**
     * Get unread notification count for a user.
     */
    public static function getUnreadCount(User $user): int
    {
        return NotificationReadStatus::where('user_id', $user->id)
            ->where('is_read', false)
            ->whereHas('notification', function ($query) {
                $query->where('is_active', true);
            })
            ->count();
    }

    /**
     * Get user notifications (recent) with read status.
     */
    public static function getUserNotifications(User $user, int $limit = 10)
    {
        return Notification::where('is_active', true)
            ->with(['readStatuses' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notification) use ($user) {
                $readStatus = $notification->readStatuses->first();
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->notification_type,
                    'created_at' => $notification->created_at,
                    'is_read' => $readStatus ? $readStatus->is_read : false,
                    'batch_id' => $notification->batch_id,
                    'observation_report_id' => $notification->observation_report_id,
                ];
            });
    }

    /**
     * Mark a notification as read for a user.
     */
    public static function markAsRead(User $user, int $notificationId): void
    {
        $readStatus = NotificationReadStatus::where('user_id', $user->id)
            ->where('notification_id', $notificationId)
            ->first();

        if ($readStatus) {
            $readStatus->is_read = true;
            $readStatus->read_at = now();
            $readStatus->save();
        }
    }

    /**
     * Mark all notifications as read for a user.
     */
    public static function markAllAsRead(User $user): void
    {
        NotificationReadStatus::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Create a new global notification (for all approved users).
     */
    public static function createGlobalNotification(
        string $type,
        string $title,
        string $message,
        ?User $createdBy = null,
        ?Batch $batch = null,
        ?ObservationReport $report = null
    ): Notification {
        $notification = Notification::create([
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
            'created_by_id' => $createdBy?->id,
            'batch_id' => $batch?->id,
            'observation_report_id' => $report?->id,
            'is_active' => true,
        ]);

        // Create read status for all approved users
        $users = User::where('is_approved', true)->get();
        foreach ($users as $user) {
            NotificationReadStatus::create([
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'is_read' => false,
            ]);
        }

        return $notification;
    }
}