<?php

declare(strict_types=1);

namespace App\Core;

/**
 * JWT helper – native HS256 implementation (no external dependencies).
 *
 * @license MIT
 */
class JWT
{
    private const ALGORITHM = 'HS256';

    // ── Encode ───────────────────────────────────────────────

    public static function encode(array $payload, int $expireSeconds = 3600): string
    {
        $now = time();
        $payload['iat'] ??= $now;
        $payload['exp'] ??= $now + $expireSeconds;

        $header    = self::base64UrlEncode((string) json_encode(['typ' => 'JWT', 'alg' => self::ALGORITHM], JSON_THROW_ON_ERROR));
        $claims    = self::base64UrlEncode((string) json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = self::sign("{$header}.{$claims}");

        return "{$header}.{$claims}.{$signature}";
    }

    // ── Decode ───────────────────────────────────────────────

    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header64, $claims64, $sig64] = $parts;

        // Verify signature
        $expected = self::sign("{$header64}.{$claims64}");
        if (!hash_equals($expected, $sig64)) {
            Logger::warning('JWT signature verification failed.');
            return null;
        }

        // Decode header
        $header = json_decode(self::base64UrlDecode($header64), true);
        if (!is_array($header) || ($header['alg'] ?? '') !== self::ALGORITHM) {
            return null;
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($claims64), true);
        if (!is_array($payload)) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            Logger::debug('JWT token expired.');
            return null;
        }

        // Check not-before
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return null;
        }

        return $payload;
    }

    // ── From Request ─────────────────────────────────────────

    public static function fromRequest(): ?array
    {
        $token = Request::bearerToken();
        return $token !== null ? self::decode($token) : null;
    }

    // ── Internals ────────────────────────────────────────────

    private static function sign(string $data): string
    {
        $hash = hash_hmac('sha256', $data, (string) APP_KEY, true);
        return self::base64UrlEncode($hash);
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $padded = str_pad(strtr($data, '-_', '+/'), strlen($data) + (4 - strlen($data) % 4) % 4, '=');
        $decoded = base64_decode($padded, true);
        return $decoded === false ? '' : $decoded;
    }
}
