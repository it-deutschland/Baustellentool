<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP response helper.
 */
class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        self::setStatus($status);
        self::setHeader('Content-Type', 'application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    public static function redirect(string $url, int $status = 302): void
    {
        self::setStatus($status);
        self::setHeader('Location', $url);
        exit;
    }

    public static function setHeader(string $name, string $value): void
    {
        header($name . ': ' . $value, true);
    }

    public static function setStatus(int $code): void
    {
        http_response_code($code);
    }

    public static function notFound(): void
    {
        self::setStatus(404);

        if (is_file(VIEWS_PATH . '/errors/404.php')) {
            View::render('errors/404', [], 'none');
            exit;
        }

        echo '404 Not Found';
        exit;
    }

    public static function forbidden(): void
    {
        self::setStatus(403);

        if (is_file(VIEWS_PATH . '/errors/403.php')) {
            View::render('errors/403', [], 'none');
            exit;
        }

        echo '403 Forbidden';
        exit;
    }
}
