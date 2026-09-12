<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Material model.
 */
class Material extends Model
{
    protected static string $table = 'materials';

    /**
     * Check whether the material is below minimum stock.
     */
    public function isLowStock(): bool
    {
        return (float) ($this->stock ?? 0) <= (float) ($this->min_stock ?? 0);
    }

    /**
     * Get distinct material categories.
     *
     * @return array<int, string>
     */
    public static function getCategories(): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT DISTINCT `category` FROM `materials` ORDER BY `category` ASC'
        );

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['category'] ?? ''),
            $rows
        )));
    }

    /**
     * Search materials.
     *
     * @return array<int, static>
     */
    public static function search(string $q): array
    {
        $term = '%' . trim($q) . '%';
        $rows = Database::getInstance()->fetchAll(
            'SELECT *
             FROM `materials`
             WHERE `name` LIKE :term
                OR `article_no` LIKE :term
                OR `description` LIKE :term
                OR `supplier` LIKE :term
             ORDER BY `category` ASC, `name` ASC',
            [':term' => $term]
        );

        return array_map(static fn (array $row): static => new static($row), $rows);
    }

    /**
     * Count materials by category.
     *
     * @return array<string, int>
     */
    public static function countByCategory(): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT `category`, COUNT(*) AS `aggregate`
             FROM `materials`
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
