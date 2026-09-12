<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use DateTimeImmutable;

/**
 * User model.
 */
class User extends Model
{
    protected static string $table = 'users';

    /**
     * Find a user by email address.
     */
    public static function findByEmail(string $email): ?static
    {
        return parent::findBy('email', $email);
    }

    /**
     * Get the assigned role.
     */
    public function getRole(): ?Role
    {
        $roleId = (int) ($this->role_id ?? 0);

        return $roleId > 0 ? Role::find($roleId) : null;
    }

    /**
     * Get all permissions for the user role.
     *
     * @return array<int, Permission>
     */
    public function getPermissions(): array
    {
        $roleId = (int) ($this->role_id ?? 0);
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
     * Check whether the user has a named permission.
     */
    public function hasPermission(string $permName): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach ($this->getPermissions() as $permission) {
            if ((string) $permission->name === $permName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the user is an administrator.
     */
    public function isAdmin(): bool
    {
        $role = $this->getRole();

        return $role !== null && strtolower((string) $role->name) === 'admin';
    }

    /**
     * Increment failed login attempts and temporarily lock after too many tries.
     */
    public function incrementLoginAttempts(): void
    {
        $attempts = (int) ($this->login_attempts ?? 0) + 1;
        $this->login_attempts = $attempts;

        if ($attempts >= 5) {
            $this->locked_until = (new DateTimeImmutable('+15 minutes'))->format('Y-m-d H:i:s');
        }

        parent::save();
    }

    /**
     * Reset failed login attempts and lock state.
     */
    public function resetLoginAttempts(): void
    {
        $this->login_attempts = 0;
        $this->locked_until = null;

        parent::save();
    }

    /**
     * Check whether the user account is currently locked.
     */
    public function isLocked(): bool
    {
        $lockedUntil = $this->locked_until;
        if (!is_string($lockedUntil) || $lockedUntil === '') {
            return false;
        }

        $timestamp = strtotime($lockedUntil);

        return $timestamp !== false && $timestamp > time();
    }

    /**
     * Generate a time-limited user token.
     */
    public function generateToken(int $hours = 24): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $this->token = hash('sha256', $rawToken);
        $this->token_expires_at = (new DateTimeImmutable(sprintf('+%d hours', max(1, $hours))))
            ->format('Y-m-d H:i:s');

        parent::save();

        return $rawToken;
    }

    /**
     * Verify a previously generated token.
     */
    public function verifyToken(string $token): bool
    {
        $storedToken = (string) ($this->token ?? '');
        $expiresAt = (string) ($this->token_expires_at ?? '');

        if ($storedToken === '' || $expiresAt === '') {
            return false;
        }

        $timestamp = strtotime($expiresAt);
        if ($timestamp === false || $timestamp <= time()) {
            return false;
        }

        $hashedToken = hash('sha256', $token);

        return hash_equals($storedToken, $hashedToken) || hash_equals($storedToken, $token);
    }

    /**
     * Get notifications for the user.
     *
     * @return array<int, Notification>
     */
    public function getNotifications(bool $unreadOnly = false): array
    {
        $userId = (int) ($this->id ?? 0);
        if ($userId <= 0) {
            return [];
        }

        $sql = 'SELECT * FROM `notifications` WHERE `user_id` = :user_id';
        if ($unreadOnly) {
            $sql .= ' AND `read_at` IS NULL';
        }
        $sql .= ' ORDER BY `created_at` DESC';

        $rows = Database::getInstance()->fetchAll($sql, [':user_id' => $userId]);

        return array_map(static fn (array $row): Notification => new Notification($row), $rows);
    }

    /**
     * Count unread notifications.
     */
    public function countUnreadNotifications(): int
    {
        $userId = (int) ($this->id ?? 0);

        return $userId > 0 ? Notification::unreadCount($userId) : 0;
    }

    /**
     * Get projects created by or assigned to the user.
     *
     * @return array<int, Project>
     */
    public function getProjects(): array
    {
        $userId = (int) ($this->id ?? 0);
        if ($userId <= 0) {
            return [];
        }

        $rows = Database::getInstance()->fetchAll(
            'SELECT DISTINCT p.*
             FROM `projects` p
             WHERE p.`created_by` = :user_id OR p.`assigned_to` = :user_id
             ORDER BY p.`updated_at` DESC',
            [':user_id' => $userId]
        );

        return array_map(static fn (array $row): Project => new Project($row), $rows);
    }
}
