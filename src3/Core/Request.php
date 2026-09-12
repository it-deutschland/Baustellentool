<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP request wrapper.
 */
class Request
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? self::json()[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return array_replace(self::json(), $_GET, $_POST);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function file(string $key): ?array
    {
        return isset($_FILES[$key]) && is_array($_FILES[$key]) ? $_FILES[$key] : null;
    }

    public static function method(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $override = $_POST['_method'] ?? ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null);

        if (is_string($override) && $override !== '') {
            $candidate = strtoupper($override);
            if (in_array($candidate, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $candidate;
            }
        }

        return $method;
    }

    public static function uri(): string
    {
        $uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $uri = rawurldecode($uri);

        if ($uri === '') {
            return '/';
        }

        $normalized = rtrim($uri, '/');

        return $normalized === '' ? '/' : $normalized;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function isAjax(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public static function ip(): string
    {
        $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        if (is_string($forwardedFor) && $forwardedFor !== '') {
            return trim(explode(',', $forwardedFor)[0]);
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function bearerToken(): ?string
    {
        $header = self::authorizationHeader();
        if ($header === null || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function json(): array
    {
        static $decoded;

        if (is_array($decoded)) {
            return $decoded;
        }

        $input = file_get_contents('php://input');
        if (!is_string($input) || trim($input) === '') {
            return $decoded = [];
        }

        $data = json_decode($input, true);

        return $decoded = is_array($data) ? $data : [];
    }

    private static function authorizationHeader(): ?string
    {
        $headers = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? null,
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        ];

        if (function_exists('getallheaders')) {
            $allHeaders = getallheaders();
            $headers[] = $allHeaders['Authorization'] ?? $allHeaders['authorization'] ?? null;
        }

        foreach ($headers as $header) {
            if (is_string($header) && $header !== '') {
                return $header;
            }
        }

        return null;
    }
}
