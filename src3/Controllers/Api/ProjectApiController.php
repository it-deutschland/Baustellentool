<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;

/**
 * API endpoints for projects.
 */
class ProjectApiController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireJwtUser();

        $page = max(1, (int) Request::get('page', 1));
        $perPage = min(100, max(1, (int) Request::get('per_page', 20)));
        $offset = ($page - 1) * $perPage;
        $status = trim((string) Request::get('status', ''));
        $params = [];
        $where = '1 = 1';
        if ($status !== '') {
            $where .= ' AND `status` = :status';
            $params[':status'] = $status;
        }

        $count = $this->db()->fetch('SELECT COUNT(*) AS `aggregate` FROM `projects` WHERE ' . $where, $params);
        $projects = $this->db()->fetchAll(
            'SELECT * FROM `projects` WHERE ' . $where . ' ORDER BY `created_at` DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        $this->json([
            'data' => $projects,
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

        $project = $this->db()->fetch(
            'SELECT p.*, c.`company` AS `customer_name`
             FROM `projects` p
             LEFT JOIN `customers` c ON c.`id` = p.`customer_id`
             WHERE p.`id` = :id
             LIMIT 1',
            [':id' => (int) $id]
        );
        if ($project === false) {
            $this->json(['message' => 'Project not found.'], 404);
        }

        $project['plans'] = $this->db()->fetchAll('SELECT * FROM `plans` WHERE `project_id` = :id ORDER BY `updated_at` DESC', [':id' => (int) $id]);
        $project['documents'] = $this->db()->fetchAll('SELECT * FROM `documents` WHERE `project_id` = :id ORDER BY `updated_at` DESC', [':id' => (int) $id]);
        $this->json($project);
    }

    public function store(): void
    {
        $user = $this->requireJwtUser();
        $this->ensureCsrf();
        if (!in_array((string) ($user['role'] ?? ''), ['admin', 'editor'], true)) {
            $this->json(['message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make(Request::all(), [
            'title' => 'required|max:200',
        ]);
        if ($validator->fails()) {
            $this->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'customer_id' => Request::input('customer_id') === '' ? null : (int) Request::input('customer_id'),
            'created_by' => (int) $user['id'],
            'assigned_to' => Request::input('assigned_to') === '' ? null : (int) Request::input('assigned_to'),
            'project_number' => 'API-' . date('YmdHis'),
            'title' => trim((string) Request::input('title')),
            'description' => trim((string) Request::input('description', '')) ?: null,
            'location' => trim((string) Request::input('location', '')) ?: null,
            'address' => trim((string) Request::input('address', '')) ?: null,
            'start_date' => trim((string) Request::input('start_date', '')) ?: null,
            'end_date' => trim((string) Request::input('end_date', '')) ?: null,
            'status' => trim((string) Request::input('status', 'draft')) ?: 'draft',
            'priority' => trim((string) Request::input('priority', 'normal')) ?: 'normal',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db()->table('projects')->insert($payload);
        $projectId = (int) $this->db()->lastInsertId();
        $this->recordLog('api_project_created', 'api', $projectId, 'project', [], $payload);
        $this->json(['id' => $projectId, 'message' => 'Project created successfully.'], 201);
    }

    public function update(string $id): void
    {
        $user = $this->requireJwtUser();
        if (!in_array((string) ($user['role'] ?? ''), ['admin', 'editor'], true)) {
            $this->json(['message' => 'Forbidden.'], 403);
        }

        $project = $this->requireRecord('projects', $id);
        $update = [
            'customer_id' => Request::input('customer_id') === '' ? null : (int) Request::input('customer_id', $project['customer_id'] ?? null),
            'assigned_to' => Request::input('assigned_to') === '' ? null : (int) Request::input('assigned_to', $project['assigned_to'] ?? null),
            'title' => trim((string) Request::input('title', (string) $project['title'])) ?: (string) $project['title'],
            'description' => trim((string) Request::input('description', (string) ($project['description'] ?? ''))) ?: null,
            'location' => trim((string) Request::input('location', (string) ($project['location'] ?? ''))) ?: null,
            'address' => trim((string) Request::input('address', (string) ($project['address'] ?? ''))) ?: null,
            'start_date' => trim((string) Request::input('start_date', (string) ($project['start_date'] ?? ''))) ?: null,
            'end_date' => trim((string) Request::input('end_date', (string) ($project['end_date'] ?? ''))) ?: null,
            'status' => trim((string) Request::input('status', (string) ($project['status'] ?? 'draft'))) ?: 'draft',
            'priority' => trim((string) Request::input('priority', (string) ($project['priority'] ?? 'normal'))) ?: 'normal',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db()->table('projects')->where('id', (int) $id)->update($update);
        $this->recordLog('api_project_updated', 'api', (int) $id, 'project', $project, $update);
        $this->json(['message' => 'Project updated successfully.']);
    }

    public function destroy(string $id): void
    {
        $user = $this->requireJwtUser();
        if ((string) ($user['role'] ?? '') !== 'admin') {
            $this->json(['message' => 'Forbidden.'], 403);
        }

        $project = $this->requireRecord('projects', $id);
        $this->db()->table('projects')->where('id', (int) $id)->delete();
        $this->recordLog('api_project_deleted', 'api', (int) $id, 'project', $project);
        $this->json(['message' => 'Project deleted successfully.']);
    }
}
