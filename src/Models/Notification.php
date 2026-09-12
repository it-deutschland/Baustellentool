<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use DateTimeImmutable;

/**
 * Notification model.
 */
class Notification extends Model
{
    protected static string $table = 'notifications';

    /**
     * Create and persist a notification.
     */
    public static function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): static {
        $notification = new static([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);

        $notification->save();

        return $notification;
    }

    /**
     * Get notifications for a user.
     *
     * @return array<int, static>
     */
    public static function forUser(int $userId): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT *
             FROM `notifications`
             WHERE `user_id` = :user_id
             ORDER BY `read_at` ASC, `created_at` DESC',
            [':user_id' => $userId]
        );

        return array_map(static fn (array $row): static => new static($row), $rows);
    }

    /**
     * Count unread notifications for a user.
     */
    public static function unreadCount(int $userId): int
    {
        $row = Database::getInstance()->fetch(
            'SELECT COUNT(*) AS `aggregate`
             FROM `notifications`
             WHERE `user_id` = :user_id AND `read_at` IS NULL',
            [':user_id' => $userId]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * Mark the notification as read.
     */
    public function markRead(): bool
    {
        $this->read_at = $this->read_at ?? (new DateTimeImmutable())->format('Y-m-d H:i:s');

        return parent::save();
    }
}
