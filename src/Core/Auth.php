<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Authentication helper.
 */
class Auth
{
    private const SESSION_KEY = 'auth_user';

    /**
     * @param array<string, mixed> $user
     */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, $user);
    }

    public static function logout(): void
    {
        Session::delete(self::SESSION_KEY);
        Session::regenerate();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        $user = Session::get(self::SESSION_KEY);

        return is_array($user) ? $user : null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        if ($user === null || !isset($user['id'])) {
            return null;
        }

        return (int) $user['id'];
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function hasRole(string $role): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        $userRole = strtolower((string) ($user['role'] ?? $user['role_name'] ?? ''));

        if ($userRole === '' && isset($user['role_id'])) {
            $row = Database::getInstance()->fetch(
                'SELECT `name` FROM `roles` WHERE `id` = :id LIMIT 1',
                [':id' => $user['role_id']]
            );
            $userRole = strtolower((string) ($row['name'] ?? ''));
        }

        return $userRole !== '' && $userRole === strtolower($role);
    }

    public static function hasPermission(string $permission): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        if (self::hasRole('admin')) {
            return true;
        }

        $permissions = $user['permissions'] ?? [];
        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);
            $permissions = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $permissions)));
        }

        if ($permissions === [] && isset($user['role_id'])) {
            $rows = Database::getInstance()->fetchAll(
                'SELECT p.`name`
                 FROM `permissions` p
                 INNER JOIN `role_permissions` rp ON rp.`permission_id` = p.`id`
                 WHERE rp.`role_id` = :role_id',
                [':role_id' => $user['role_id']]
            );
            $permissions = array_column($rows, 'name');
        }

        return is_array($permissions) && in_array($permission, $permissions, true);
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::getInstance()->fetch(
            'SELECT u.*, r.`name` AS `role`
             FROM `users` u
             LEFT JOIN `roles` r ON r.`id` = u.`role_id`
             WHERE u.`email` = :email
             LIMIT 1',
            [':email' => $email]
        );

        if ($user === false) {
            return false;
        }

        $hash = $user['password'] ?? $user['password_hash'] ?? null;
        if (!is_string($hash) || !self::verifyPassword($password, $hash)) {
            return false;
        }

        self::login($user);

        return true;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
