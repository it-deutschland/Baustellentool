<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * PHP view renderer.
 */
class View
{
    /**
     * @param array<string, mixed> $data
     *
     * @throws RuntimeException
     */
    public static function render(string $view, array $data = [], string $layout = 'default'): void
    {
        $viewFile = self::pathFor($view);
        if (!is_file($viewFile)) {
            throw new RuntimeException(sprintf('View [%s] not found.', $view));
        }

        $content = self::capture($viewFile, $data);

        if ($layout === 'none') {
            echo $content;

            return;
        }

        $layoutFile = VIEWS_PATH . '/layout/' . trim($layout, '/') . '.php';
        if (!is_file($layoutFile)) {
            echo $content;

            return;
        }

        echo self::capture($layoutFile, array_merge($data, ['content' => $content]));
    }

    public static function e(mixed $val): string
    {
        return htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    }

    private static function pathFor(string $view): string
    {
        $relative = str_replace(['.', '\\'], '/', trim($view, '/'));

        return VIEWS_PATH . '/' . $relative . '.php';
    }
}
