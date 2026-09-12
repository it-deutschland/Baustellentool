<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Permission model.
 */
class Permission extends Model
{
    protected static string $table = 'permissions';

    /**
     * Get all permissions grouped by module.
     *
     * @return array<string, array<int, static>>
     */
    public static function getAllGrouped(): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT * FROM `permissions` ORDER BY `module` ASC, `display_name` ASC'
        );

        $grouped = [];
        foreach ($rows as $row) {
            $module = (string) ($row['module'] ?? 'general');
            $grouped[$module][] = new static($row);
        }

        return $grouped;
    }
}
