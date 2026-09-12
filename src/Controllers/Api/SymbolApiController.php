<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Controller;
use App\Core\Request;
use App\Services\SymbolCatalogService;

/**
 * API endpoints for symbols.
 */
class SymbolApiController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $search = trim((string) Request::get('search', ''));
        $category = trim((string) Request::get('category', ''));
        $subcategory = trim((string) Request::get('subcategory', ''));
        $editorCategory = trim((string) Request::get('editor_category', ''));
        $where = ['1 = 1'];
        $params = [];
        if ($search !== '') {
            $where[] = '(`name` LIKE :search OR `description` LIKE :search OR `tags` LIKE :search OR `sign_number` LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        if ($category !== '') {
            $where[] = '`category` = :category';
            $params[':category'] = $category;
        }
        if ($subcategory !== '') {
            $where[] = '`subcategory` = :subcategory';
            $params[':subcategory'] = $subcategory;
        }

        $databaseSymbols = $this->db()->fetchAll(
            'SELECT * FROM `symbols` WHERE ' . implode(' AND ', $where) . ' ORDER BY `category`, `name`',
            $params
        );

        $existingStaticKeys = [];
        $symbols = [];

        foreach ($databaseSymbols as $symbol) {
            $symbol = $this->normalizeSymbolRecord($symbol);
            if ($editorCategory !== '' && (string) ($symbol['editor_category'] ?? '') !== $editorCategory) {
                continue;
            }
            $symbols[] = $symbol;

            if (($symbol['source'] ?? '') === 'RSA static symbols') {
                $existingStaticKeys[(string) ($symbol['sign_number'] ?? '')] = true;
            }

            $filePath = (string) ($symbol['file_path'] ?? '');
            if (str_starts_with($filePath, '/symbole/')) {
                $existingStaticKeys[basename($filePath, '.jpg')] = true;
            }
        }

        foreach ((new SymbolCatalogService())->all() as $symbol) {
            $staticKey = (string) ($symbol['sign_number'] ?? '');
            if ($staticKey !== '' && isset($existingStaticKeys[$staticKey])) {
                continue;
            }
            if (!$this->matchesStaticSymbol($symbol, $search, $category, $subcategory, $editorCategory)) {
                continue;
            }

            $symbols[] = $this->normalizeSymbolRecord($symbol);
        }

        usort($symbols, static function (array $left, array $right): int {
            return [(string) ($left['editor_category'] ?? ''), (string) ($left['subcategory'] ?? ''), (string) ($left['sign_number'] ?? ''), (string) ($left['name'] ?? '')]
                <=>
                [(string) ($right['editor_category'] ?? ''), (string) ($right['subcategory'] ?? ''), (string) ($right['sign_number'] ?? ''), (string) ($right['name'] ?? '')];
        });

        $this->json(['data' => $symbols]);
    }

    public function show(string $id): void
    {
        $symbol = $this->findRecord('symbols', $id);
        if ($symbol === null) {
            $this->json(['message' => 'Symbol not found.'], 404);
        }

        $this->json($symbol);
    }

    /**
     * @param array<string, mixed> $symbol
     */
    private function matchesStaticSymbol(array $symbol, string $search, string $category, string $subcategory, string $editorCategory): bool
    {
        if ($search !== '') {
            $haystack = mb_strtolower(implode(' ', [
                (string) ($symbol['name'] ?? ''),
                (string) ($symbol['description'] ?? ''),
                (string) ($symbol['tags'] ?? ''),
                (string) ($symbol['sign_number'] ?? ''),
            ]));

            if (!str_contains($haystack, mb_strtolower($search))) {
                return false;
            }
        }

        if ($category !== '' && (string) ($symbol['official_category'] ?? $symbol['category'] ?? '') !== $category) {
            return false;
        }

        if ($subcategory !== '' && (string) ($symbol['subcategory'] ?? '') !== $subcategory) {
            return false;
        }

        if ($editorCategory !== '' && (string) ($symbol['editor_category'] ?? '') !== $editorCategory) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $symbol
     * @return array<string, mixed>
     */
    private function normalizeSymbolRecord(array $symbol): array
    {
        $filePath = trim((string) ($symbol['file_path'] ?? ''));
        if ($filePath !== '') {
            $symbol['file_url'] = str_starts_with($filePath, '/') ? $filePath : '/dateien/symbols/' . ltrim($filePath, '/');
        }

        if (!isset($symbol['official_category']) && isset($symbol['category'])) {
            $symbol['official_category'] = $symbol['category'];
        }

        if (!isset($symbol['editor_category'])) {
            $number = (string) ($symbol['sign_number'] ?? '');
            $description = (string) ($symbol['description'] ?? $symbol['name'] ?? '');
            $officialCategory = (string) ($symbol['official_category'] ?? '');
            $symbol['editor_category'] = $this->inferEditorCategory($number, $description, $officialCategory);
        }

        return $symbol;
    }

    private function inferEditorCategory(string $number, string $description, string $officialCategory): string
    {
        if (preg_match('/^10\\d{2}/', $number) === 1) {
            return '09 Zusatzzeichen';
        }

        if (str_contains($description, 'Bake')) {
            return '10 Leitbaken';
        }

        if ($officialCategory === 'Verkehrseinrichtungen') {
            return '11 Verkehrseinrichtungen';
        }

        return '08 Verkehrszeichen';
    }
}
