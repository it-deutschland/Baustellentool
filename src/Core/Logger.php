<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Supported logger levels.
 */
enum LogLevel: int
{
    case Debug = 100;
    case Info = 200;
    case Warning = 300;
    case Error = 400;

    public static function fromString(string $level): self
    {
        return match (strtolower($level)) {
            'debug' => self::Debug,
            'warning' => self::Warning,
            'error' => self::Error,
            default => self::Info,
        };
    }
}

/**
 * File-based logger.
 */
class Logger
{
    private const MAX_FILE_SIZE = 10485760;

    public static function log(string $level, string $message, array $context = []): void
    {
        $configuredLevel = defined('LOG_LEVEL') ? (string) LOG_LEVEL : ((defined('APP_DEBUG') && APP_DEBUG) ? 'debug' : 'info');
        $requestedLevel = LogLevel::fromString($level);

        if ($requestedLevel->value < LogLevel::fromString($configuredLevel)->value) {
            return;
        }

        $directory = defined('LOG_PATH') ? (string) LOG_PATH : BASE_PATH . '/infos';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $file = $directory . '/app.log';
        self::rotateIfNeeded($file);

        $payload = sprintf(
            "[%s] %s: %s %s%s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context === [] ? '{}' : (json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'),
            PHP_EOL
        );

        file_put_contents($file, $payload, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    private static function rotateIfNeeded(string $file): void
    {
        if (!is_file($file) || filesize($file) <= self::MAX_FILE_SIZE) {
            return;
        }

        $archived = sprintf('%s/app-%s.log', dirname($file), date('Ymd-His'));
        rename($file, $archived);
    }
}
