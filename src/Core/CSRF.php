<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * CSRF token helper.
 */
class CSRF
{
    private const SESSION_KEY = '_csrf_token';

    public static function generate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::SESSION_KEY, $token);

        return $token;
    }

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);

        return is_string($token) && $token !== '' ? $token : self::generate();
    }

    public static function field(): string
    {
        return sprintf('<input type="hidden" name="_token" value="%s">', View::e(self::token()));
    }

    public static function verify(string $token): bool
    {
        $sessionToken = Session::get(self::SESSION_KEY);

        return is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token);
    }

    /**
     * @throws RuntimeException
     */
    public static function check(): void
    {
        if (in_array(Request::method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = Request::input('_token')
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)
            ?? ($_SERVER['HTTP_X_XSRF_TOKEN'] ?? null);

        if (!is_string($token) || !self::verify($token)) {
            throw new RuntimeException('Invalid CSRF token.');
        }
    }
}
