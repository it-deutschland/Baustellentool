<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Symbol model.
 */
class Symbol extends Model
{
    protected static string $table = 'symbols';

    /**
     * Get distinct categories and subcategories.
     *
     * @return array<string, array<int, string>>
     */
    public static function getCategories(): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT DISTINCT `category`, `subcategory`
             FROM `symbols`
             ORDER BY `category` ASC, `subcategory` ASC'
        );

        $categories = [];
        foreach ($rows as $row) {
            $category = (string) ($row['category'] ?? '');
            $subcategory = trim((string) ($row['subcategory'] ?? ''));
            if ($category === '') {
                continue;
            }

            $categories[$category] ??= [];
            if ($subcategory !== '' && !in_array($subcategory, $categories[$category], true)) {
                $categories[$category][] = $subcategory;
            }
        }

        return $categories;
    }

    /**
     * Search symbols by name, tags, and sign number.
     *
     * @return array<int, static>
     */
    public static function search(string $q, string $category = ''): array
    {
        $sql = 'SELECT *
                FROM `symbols`
                WHERE (`name` LIKE :term OR `tags` LIKE :term OR `sign_number` LIKE :term)';
        $params = [':term' => '%' . trim($q) . '%'];

        if ($category !== '') {
            $sql .= ' AND `category` = :category';
            $params[':category'] = $category;
        }

        $sql .= ' ORDER BY `category` ASC, `name` ASC';

        $rows = Database::getInstance()->fetchAll($sql, $params);

        return array_map(static fn (array $row): static => new static($row), $rows);
    }

    /**
     * Get favourite symbols.
     *
     * @return array<int, static>
     */
    public static function getFavourites(): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT * FROM `symbols` WHERE `is_favourite` = 1 ORDER BY `category` ASC, `name` ASC'
        );

        return array_map(static fn (array $row): static => new static($row), $rows);
    }

    /**
     * Get the public file URL.
     */
    public function getFileUrl(): string
    {
        $path = ltrim((string) ($this->file_path ?? ''), '/');
        $path = preg_replace('#^(dateien|uploads)/symbols/#', '', $path) ?? $path;

        return '/dateien/symbols/' . $path;
    }

    /**
     * Count symbols by category.
     *
     * @return array<string, int>
     */
    public static function countByCategory(): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT `category`, COUNT(*) AS `aggregate`
             FROM `symbols`
             GROUP BY `category`
             ORDER BY `category` ASC'
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['category']] = (int) ($row['aggregate'] ?? 0);
        }

        return $counts;
    }
}
