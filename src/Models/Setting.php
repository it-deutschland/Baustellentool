<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Setting model.
 */
class Setting extends Model
{
    protected static string $table = 'settings';

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = parent::findBy('key', $key);

        return $setting instanceof static ? self::castValue($setting->value, (string) ($setting->type ?? 'text')) : $default;
    }

    /**
     * Persist a setting value by key.
     */
    public static function set(string $key, mixed $value): bool
    {
        $setting = parent::findBy('key', $key) ?? new static([
            'key' => $key,
            'label' => $key,
            'group' => 'general',
            'type' => self::detectType($value),
        ]);

        $setting->type = (string) ($setting->type ?? self::detectType($value));
        $setting->value = self::normalizeValue($value, (string) $setting->type);

        return $setting->save();
    }

    /**
     * Get all settings within a group.
     *
     * @return array<string, mixed>
     */
    public static function getGroup(string $group): array
    {
        $settings = parent::where('group', $group);
        $values = [];

        foreach ($settings as $setting) {
            $values[(string) $setting->key] = self::castValue($setting->value, (string) ($setting->type ?? 'text'));
        }

        return $values;
    }

    /**
     * Get all settings as key-value pairs.
     *
     * @return array<string, mixed>
     */
    public static function getAll(): array
    {
        $values = [];
        foreach (parent::all('key', 'ASC') as $setting) {
            $values[(string) $setting->key] = self::castValue($setting->value, (string) ($setting->type ?? 'text'));
        }

        return $values;
    }

    /**
     * Convert a DB value to a PHP value.
     */
    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => in_array((string) $value, ['1', 'true', 'yes', 'on'], true),
            'number' => is_numeric($value) ? ((string) (int) $value === (string) $value ? (int) $value : (float) $value) : $value,
            'json' => is_string($value) && $value !== '' ? (json_decode($value, true) ?? []) : [],
            default => $value,
        };
    }

    /**
     * Normalize a PHP value for storage.
     */
    private static function normalizeValue(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE) ?: '[]',
            default => (string) $value,
        };
    }

    /**
     * Detect the storage type for a value.
     */
    private static function detectType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value) || is_float($value)) {
            return 'number';
        }

        if (is_array($value)) {
            return 'json';
        }

        return 'text';
    }
}
