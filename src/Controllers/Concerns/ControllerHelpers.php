<?php

declare(strict_types=1);

namespace App\Controllers\Concerns;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\JWT;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Throwable;

/**
 * Shared controller helpers.
 */
trait ControllerHelpers
{
    protected function db(): Database
    {
        return Database::getInstance();
    }

    protected function ensureCsrf(): void
    {
        if (Request::isPost()) {
            CSRF::check();
        }
    }

    protected function enforceRole(string $role): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Please sign in to continue.');
            Response::redirect('/login');
        }

        if (!Auth::hasRole($role) && !Auth::hasRole('admin')) {
            Session::flash('error', 'You do not have permission to access that page.');
            Response::redirect('/dashboard');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findRecord(string $table, int|string $id): ?array
    {
        return $this->db()->table($table)->where('id', (int) $id)->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function requireRecord(string $table, int|string $id): array
    {
        $record = $this->findRecord($table, $id);
        if ($record === null) {
            Response::notFound();
        }

        return $record ?? [];
    }

    /**
     * @param array<string, array<int, string>> $errors
     */
    protected function flashValidation(array $errors, array $old = []): void
    {
        Session::flash('errors', $errors);
        if ($old !== []) {
            unset($old['password'], $old['password_confirmation'], $old['old_password'], $old['_token']);
            Session::flash('old', $old);
        }
    }

    protected function settingValue(string $key, ?string $default = null): ?string
    {
        $row = $this->db()->table('settings')->where('key', $key)->first();

        return isset($row['value']) ? (string) $row['value'] : $default;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function groupedSettings(): array
    {
        $settings = $this->db()->fetchAll('SELECT * FROM `settings` ORDER BY `group`, `label`, `key`');
        $grouped = [];

        foreach ($settings as $setting) {
            $group = (string) ($setting['group'] ?? 'general');
            $grouped[$group] ??= [];
            $grouped[$group][] = $setting;
        }

        return $grouped;
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    /**
     * @param array<string, mixed> $file
     * @param array<int, string>|null $allowedExtensions
     *
     * @return array<string, mixed>
     */
    protected function storeUploadedFile(
        array $file,
        string $directory,
        ?array $allowedExtensions = null,
        ?int $maxSize = null
    ): array {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed.');
        }

        $originalName = (string) ($file['name'] ?? 'upload.bin');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = array_map(
            'strtolower',
            $allowedExtensions ?? (defined('ALLOWED_EXTENSIONS') ? ALLOWED_EXTENSIONS : ['svg', 'png', 'jpg', 'jpeg', 'pdf', 'zip'])
        );

        if ($allowed !== [] && !in_array($extension, $allowed, true)) {
            throw new \RuntimeException('File type is not allowed.');
        }

        $size = (int) ($file['size'] ?? 0);
        $limit = $maxSize ?? (defined('UPLOAD_MAX_SIZE') ? (int) UPLOAD_MAX_SIZE : 52428800);
        if ($size > $limit) {
            throw new \RuntimeException('The uploaded file is too large.');
        }

        $uploadRoot = defined('UPLOAD_PATH') ? (string) UPLOAD_PATH : BASE_PATH . '/dateien';
        $subDirectory = trim($directory, '/');
        $targetDirectory = rtrim($uploadRoot, '/');
        if ($subDirectory !== '') {
            $targetDirectory .= '/' . $subDirectory;
        }

        $this->ensureDirectory($targetDirectory);

        $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(8));
        if ($extension !== '') {
            $storedName .= '.' . $extension;
        }

        $targetPath = $targetDirectory . '/' . $storedName;
        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || (!move_uploaded_file($tmpPath, $targetPath) && !rename($tmpPath, $targetPath))) {
            throw new \RuntimeException('Unable to store the uploaded file.');
        }

        $mimeType = (string) ($file['type'] ?? (function_exists('mime_content_type') ? mime_content_type($targetPath) : 'application/octet-stream'));
        $relativePath = '/dateien' . ($subDirectory !== '' ? '/' . $subDirectory : '') . '/' . $storedName;

        return [
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'file_path' => $relativePath,
            'absolute_path' => $targetPath,
            'file_type' => $extension,
            'mime_type' => $mimeType,
            'file_size' => $size,
        ];
    }

    protected function resolveStoragePath(string $filePath): string
    {
        $clean = ltrim(str_replace(['..', '\\'], ['', '/'], $filePath), '/');
        $uploadBase = rtrim((defined('UPLOAD_PATH') ? (string) UPLOAD_PATH : BASE_PATH . '/dateien'), '/');
        if (str_starts_with($clean, 'dateien/')) {
            $resolved = BASE_PATH . '/' . $clean;
        } elseif (str_starts_with($clean, 'uploads/')) {
            $resolved = $uploadBase . '/' . ltrim(substr($clean, strlen('uploads/')), '/');
        } else {
            $resolved = $uploadBase . '/' . $clean;
        }

        // Verify the resolved path stays within allowed base directories
        $real = realpath($resolved);
        $allowedBase = realpath(defined('UPLOAD_PATH') ? UPLOAD_PATH : BASE_PATH . '/dateien');
        $storageBase = realpath(defined('STORAGE_PATH') ? STORAGE_PATH : BASE_PATH . '/speicher');

        if ($real === false) {
            return $resolved; // file doesn't exist yet (new upload)
        }

        if (
            ($allowedBase !== false && str_starts_with($real, $allowedBase)) ||
            ($storageBase !== false && str_starts_with($real, $storageBase))
        ) {
            return $real;
        }

        // Path outside allowed directories — deny access
        \App\Core\Logger::warning('Path traversal attempt blocked.', ['path' => $filePath]);
        \App\Core\Response::forbidden();
        exit;
    }

    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     */
    protected function recordLog(
        string $action,
        string $module,
        ?int $subjectId = null,
        ?string $subjectType = null,
        array $oldValues = [],
        array $newValues = []
    ): void {
        try {
            if (class_exists('App\\Models\\Log') && method_exists('App\\Models\\Log', 'record')) {
                try {
                    \App\Models\Log::record($action, $module, $subjectId, $subjectType, $oldValues, $newValues);
                } catch (Throwable) {
                }
            }

            $this->db()->table('logs')->insert([
                'user_id' => Auth::id(),
                'action' => $action,
                'module' => $module,
                'subject_id' => $subjectId,
                'subject_type' => $subjectType,
                'old_values' => $oldValues === [] ? null : json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_values' => $newValues === [] ? null : json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip_address' => Request::ip(),
                'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $throwable) {
            Logger::error('Failed to write activity log.', ['exception' => $throwable->getMessage(), 'action' => $action]);
        }
    }

    protected function isRegistrationEnabled(): bool
    {
        $setting = $this->settingValue('registration_enabled');
        if ($setting !== null) {
            return in_array(strtolower($setting), ['1', 'true', 'yes', 'on'], true);
        }

        return defined('REGISTRATION_ENABLED') ? (bool) REGISTRATION_ENABLED : true;
    }

    protected function normalizeBoolean(mixed $value): int
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    protected function generateBase32Secret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($index = 0; $index < $length; $index++) {
            $secret .= $alphabet[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function loadUserById(int $userId): ?array
    {
        $user = $this->db()->fetch(
            'SELECT u.*, r.`name` AS `role`, r.`display_name` AS `role_name`
             FROM `users` u
             LEFT JOIN `roles` r ON r.`id` = u.`role_id`
             WHERE u.`id` = :id
             LIMIT 1',
            [':id' => $userId]
        );

        return $user === false ? null : $user;
    }

    protected function refreshAuthenticatedUser(?int $userId = null): void
    {
        $userId ??= Auth::id();
        if ($userId === null) {
            return;
        }

        $user = $this->loadUserById($userId);
        if ($user !== null) {
            Auth::login($user);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function requireJwtUser(): array
    {
        $payload = JWT::fromRequest();
        if ($payload === null) {
            $this->json(['message' => 'Unauthorized.'], 401);
        }

        $userId = (int) ($payload['sub'] ?? $payload['user_id'] ?? 0);
        $user = $userId > 0 ? $this->loadUserById($userId) : null;
        if ($user === null || (int) ($user['is_active'] ?? 1) !== 1) {
            $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $user ?? [];
    }

    protected function renderHtml(string $html, string $contentType = 'text/html; charset=UTF-8', int $status = 200): void
    {
        Response::setStatus($status);
        Response::setHeader('Content-Type', $contentType);
        echo $html;
        exit;
    }

    protected function sanitizeBackupName(string $name): string
    {
        return basename(str_replace('..', '', $name));
    }
}
