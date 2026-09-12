<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Template model.
 */
class Template extends Model
{
    protected static string $table = 'templates';

    /**
     * Get default templates.
     *
     * @return array<int, static>
     */
    public static function getDefaults(): array
    {
        return parent::where('is_default', 1);
    }

    /**
     * Get templates by type.
     *
     * @return array<int, static>
     */
    public static function getByType(string $type): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT * FROM `templates` WHERE `type` = :type ORDER BY `name` ASC',
            [':type' => $type]
        );

        return array_map(static fn (array $row): static => new static($row), $rows);
    }

    /**
     * Render template content with placeholder replacements.
     *
     * @param array<string, mixed> $vars
     */
    public function render(array $vars = []): string
    {
        $content = (string) ($this->content ?? '');

        return (string) preg_replace_callback(
            '/{{\s*([a-zA-Z0-9_.-]+)\s*}}/',
            static function (array $matches) use ($vars): string {
                $key = $matches[1] ?? '';
                $value = $vars[$key] ?? '';

                if (is_scalar($value) || $value === null) {
                    return (string) $value;
                }

                return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
            },
            $content
        ) ?? $content;
    }
}
