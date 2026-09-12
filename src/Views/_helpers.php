<?php

use App\Core\View;

if (!function_exists('rsa21_data_get')) {
    function rsa21_data_get(mixed $source, string $key, mixed $default = null): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? $default;
        }

        if (is_object($source) && isset($source->{$key})) {
            return $source->{$key};
        }

        return $default;
    }
}

if (!function_exists('rsa21_data_list')) {
    function rsa21_data_list(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}

if (!function_exists('rsa21_date')) {
    function rsa21_date(mixed $value, string $format = 'd.m.Y H:i'): string
    {
        if (!is_scalar($value) || (string) $value === '') {
            return '—';
        }

        $timestamp = strtotime((string) $value);

        return $timestamp !== false ? date($format, $timestamp) : View::e((string) $value);
    }
}

if (!function_exists('rsa21_currency')) {
    function rsa21_currency(mixed $value): string
    {
        if (!is_numeric($value)) {
            return '—';
        }

        return number_format((float) $value, 2, ',', '.') . ' €';
    }
}

if (!function_exists('rsa21_bool')) {
    function rsa21_bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }
}

if (!function_exists('rsa21_setting')) {
    function rsa21_setting(array $settings, string $key, mixed $default = ''): mixed
    {
        if (array_key_exists($key, $settings)) {
            $value = $settings[$key];

            return is_array($value) ? ($value['value'] ?? $default) : $value;
        }

        foreach ($settings as $row) {
            if (is_array($row) && ($row['key'] ?? null) === $key) {
                return $row['value'] ?? $default;
            }

            if (is_object($row) && (($row->key ?? null) === $key)) {
                return $row->value ?? $default;
            }
        }

        return $default;
    }
}

if (!function_exists('rsa21_filesize')) {
    function rsa21_filesize(mixed $bytes): string
    {
        if (!is_numeric($bytes)) {
            return '—';
        }

        $size = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        foreach ($units as $unit) {
            if ($size < 1024 || $unit === 'TB') {
                return number_format($size, $unit === 'B' ? 0 : 2, ',', '.') . ' ' . $unit;
            }
            $size /= 1024;
        }

        return number_format($size, 2, ',', '.') . ' TB';
    }
}
