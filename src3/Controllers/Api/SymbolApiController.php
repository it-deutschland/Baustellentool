<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Controller;
use App\Core\Request;

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

        $symbols = $this->db()->fetchAll(
            'SELECT * FROM `symbols` WHERE ' . implode(' AND ', $where) . ' ORDER BY `category`, `name`',
            $params
        );
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
}
