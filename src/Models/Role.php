<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Role model.
 */
class Role extends Model
{
    protected static string $table = 'roles';

    /**
     * Get permissions belonging to the role.
     *
     * @return array<int, Permission>
     */
    public function getPermissions(): array
    {
        $roleId = (int) ($this->id ?? 0);
        if ($roleId <= 0) {
            return [];
        }

        $rows = Database::getInstance()->fetchAll(
            'SELECT p.*
             FROM `permissions` p
             INNER JOIN `role_permissions` rp ON rp.`permission_id` = p.`id`
             WHERE rp.`role_id` = :role_id
             ORDER BY p.`module` ASC, p.`display_name` ASC',
            [':role_id' => $roleId]
        );

        return array_map(static fn (array $row): Permission => new Permission($row), $rows);
    }

    /**
     * Check whether the role has a named permission.
     */
    public function hasPermission(string $permName): bool
    {
        foreach ($this->getPermissions() as $permission) {
            if ((string) $permission->name === $permName) {
                return true;
            }
        }

        return false;
    }
}
