<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Loads the built-in RSA symbol catalog from the repository.
 */
class SymbolCatalogService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $file = BASE_PATH . '/database/symbol-catalog.json';
        if (!is_file($file)) {
            return [];
        }

        $raw = file_get_contents($file);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $symbols = $decoded['symbols'] ?? [];
        return is_array($symbols) ? array_values(array_filter($symbols, 'is_array')) : [];
    }
}
