<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

/**
 * Activity log model.
 */
class Log extends Model
{
    protected static string $table = 'logs';

    /**
     * Record a log entry.
     *
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     */
    public static function record(
        string $action,
        string $module,
        ?int $subjectId = null,
        ?string $subjectType = null,
        array $oldValues = [],
        array $newValues = []
    ): void {
        $forwardedFor = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        $ipAddress = $forwardedFor !== ''
            ? trim(explode(',', $forwardedFor)[0])
            : (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        $log = new static([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
            'old_values' => $oldValues === [] ? null : json_encode($oldValues, JSON_UNESCAPED_UNICODE),
            'new_values' => $newValues === [] ? null : json_encode($newValues, JSON_UNESCAPED_UNICODE),
            'ip_address' => $ipAddress,
            'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);

        $log->save();
    }

    /**
     * Get recent log entries together with user details.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recent(int $limit = 50): array
    {
        $limit = max(1, $limit);
        $rows = Database::getInstance()->fetchAll(
            sprintf(
                'SELECT l.*, u.`name` AS `user_name`, u.`email` AS `user_email`
                 FROM `logs` l
                 LEFT JOIN `users` u ON u.`id` = l.`user_id`
                 ORDER BY l.`created_at` DESC
                 LIMIT %d',
                $limit
            )
        );

        foreach ($rows as &$row) {
            $row['old_values'] = is_string($row['old_values'] ?? null) && $row['old_values'] !== ''
                ? (json_decode((string) $row['old_values'], true) ?? [])
                : [];
            $row['new_values'] = is_string($row['new_values'] ?? null) && $row['new_values'] !== ''
                ? (json_decode((string) $row['new_values'], true) ?? [])
                : [];
        }
        unset($row);

        return $rows;
    }

    /**
     * Get the related user.
     */
    public function getUser(): ?User
    {
        $userId = (int) ($this->user_id ?? 0);

        return $userId > 0 ? User::find($userId) : null;
    }
}
