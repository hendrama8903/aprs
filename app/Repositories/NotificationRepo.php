<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Support\DBResult;

/** Port dari Models/Repo/NotificationRepo.cs. */
class NotificationRepo
{
    /** @return object[] */
    public function search(int $userId, int $top = 20): array
    {
        $rows = Notification::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($top)
            ->get();

        $unreadCount = Notification::query()->where('user_id', $userId)->where('is_read', false)->count();

        return $rows->map(fn (Notification $n) => (object) [
            'NotificationId' => $n->notification_id,
            'Title' => $n->title,
            'Message' => $n->message,
            'Url' => $n->url,
            'IsRead' => $n->is_read,
            'CreatedDate' => $n->created_at,
            'UnreadCount' => $unreadCount,
        ])->values()->all();
    }

    /** $notificationId null = tandai semua terbaca. */
    public function markRead(int $userId, ?int $notificationId, string $loginUser): DBResult
    {
        Notification::query()
            ->where('user_id', $userId)
            ->when($notificationId, fn ($q) => $q->where('notification_id', $notificationId))
            ->update(['is_read' => true, 'read_at' => now()]);

        return new DBResult(true, '');
    }
}
