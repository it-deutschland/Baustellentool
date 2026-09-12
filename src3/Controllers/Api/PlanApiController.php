<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Controller;
use App\Core\Request;

/**
 * API endpoints for plans.
 */
class PlanApiController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireJwtUser();

        $page = max(1, (int) Request::get('page', 1));
        $perPage = min(100, max(1, (int) Request::get('per_page', 20)));
        $offset = ($page - 1) * $perPage;
        $count = $this->db()->fetch('SELECT COUNT(*) AS `aggregate` FROM `plans`');
        $plans = $this->db()->fetchAll(
            'SELECT pl.*, p.`title` AS `project_title`
             FROM `plans` pl
             INNER JOIN `projects` p ON p.`id` = pl.`project_id`
             ORDER BY pl.`updated_at` DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );

        $this->json([
            'data' => $plans,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => (int) ($count['aggregate'] ?? 0),
            ],
        ]);
    }

    public function show(string $id): void
    {
        $this->requireJwtUser();

        $plan = $this->db()->fetch(
            'SELECT pl.*, p.`title` AS `project_title`, p.`project_number`
             FROM `plans` pl
             INNER JOIN `projects` p ON p.`id` = pl.`project_id`
             WHERE pl.`id` = :id
             LIMIT 1',
            [':id' => (int) $id]
        );
        if ($plan === false) {
            $this->json(['message' => 'Plan not found.'], 404);
        }

        $this->json($plan);
    }
}
