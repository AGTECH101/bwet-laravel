<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationReadStatus;
use App\Models\User;

class NotificationService
{
    public static function createGlobal(string $type, string $title, string $message, ?User $createdBy = null, ?\App\Models\Poultry\Batch $batch = null, ?\App\Models\ObservationReport $report = null)
    {
        return Notification::createGlobal($type, $title, $message, $createdBy, $batch, $report);
    }

    public static function getUserNotifications(User $user)
    {
        return NotificationReadStatus::with('notification')
            ->where('user_id', $user->id)
            ->whereHas('notification', fn($q) => $q->where('is_active', true))
            ->orderByDesc('created_at')
            ->get();
    }

    public static function markAsRead(User $user, int $notificationId): bool
    {
        $status = NotificationReadStatus::where('user_id', $user->id)
            ->where('notification_id', $notificationId)
            ->first();

        if ($status && !$status->is_read) {
            $status->update(['is_read' => true, 'read_at' => now()]);
            return true;
        }
        return false;
    }

    public static function getUnreadCount(User $user): int
    {
        return NotificationReadStatus::where('user_id', $user->id)
            ->where('is_read', false)
            ->whereHas('notification', fn($q) => $q->where('is_active', true))
            ->count();
    }
}