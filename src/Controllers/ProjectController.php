<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/**
 * Manages project CRUD operations.
 */
class ProjectController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();

        $page = max(1, (int) Request::get('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $search = trim((string) Request::get('search', ''));
        $status = trim((string) Request::get('status', ''));
        $params = [];
        $where = ['1 = 1'];

        if ($search !== '') {
            $where[] = '(`p`.`title` LIKE :search OR `p`.`project_number` LIKE :search OR `c`.`company` LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        if ($status !== '') {
            $where[] = '`p`.`status` = :status';
            $params[':status'] = $status;
        }

        $whereSql = implode(' AND ', $where);
        $count = $this->db()->fetch(
            'SELECT COUNT(*) AS `aggregate`
             FROM `projects` p
             LEFT JOIN `customers` c ON c.`id` = p.`customer_id`
             WHERE ' . $whereSql,
            $params
        );
        $projects = $this->db()->fetchAll(
            'SELECT p.*, c.`company` AS `customer_name`, u.`name` AS `assigned_name`
             FROM `projects` p
             LEFT JOIN `customers` c ON c.`id` = p.`customer_id`
             LEFT JOIN `users` u ON u.`id` = p.`assigned_to`
             WHERE ' . $whereSql . '
             ORDER BY p.`created_at` DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        $total = (int) ($count['aggregate'] ?? 0);
        $this->render('projects/index', [
            'projects' => $projects,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');

        $this->render('projects/create', [
            'customers' => $this->db()->fetchAll('SELECT * FROM `customers` ORDER BY `company` ASC'),
            'users' => $this->db()->fetchAll('SELECT `id`, `name` FROM `users` WHERE `is_active` = 1 ORDER BY `name` ASC'),
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $input = Request::all();
        $validator = Validator::make($input, [
            'title' => 'required|max:200',
            'start_date' => 'max:20',
            'end_date' => 'max:20',
            'contact_email' => 'max:191',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $input);
            Session::flash('error', 'Please correct the project form.');
            $this->back();
        }

        $payload = [
            'customer_id' => $this->nullableInt(Request::post('customer_id')),
            'created_by' => Auth::id(),
            'assigned_to' => $this->nullableInt(Request::post('assigned_to')),
            'project_number' => $this->generateProjectNumber(),
            'title' => trim((string) Request::post('title')),
            'description' => trim((string) Request::post('description', '')) ?: null,
            'location' => trim((string) Request::post('location', '')) ?: null,
            'address' => trim((string) Request::post('address', '')) ?: null,
            'gps_lat' => $this->nullableNumber(Request::post('gps_lat')),
            'gps_lng' => $this->nullableNumber(Request::post('gps_lng')),
            'contact_name' => trim((string) Request::post('contact_name', '')) ?: null,
            'contact_phone' => trim((string) Request::post('contact_phone', '')) ?: null,
            'contact_email' => trim((string) Request::post('contact_email', '')) ?: null,
            'start_date' => trim((string) Request::post('start_date', '')) ?: null,
            'end_date' => trim((string) Request::post('end_date', '')) ?: null,
            'status' => trim((string) Request::post('status', 'draft')) ?: 'draft',
            'priority' => trim((string) Request::post('priority', 'normal')) ?: 'normal',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db()->table('projects')->insert($payload);
        $projectId = (int) $this->db()->lastInsertId();
        $this->recordLog('project_created', 'projects', $projectId, 'project', [], $payload);
        Session::flash('success', 'Project created successfully.');
        $this->redirect('/projects/' . $projectId);
    }

    public function show(string $id): void
    {
        $this->requireAuth();

        $project = $this->db()->fetch(
            'SELECT p.*, c.`company` AS `customer_name`, u.`name` AS `assigned_name`, creator.`name` AS `creator_name`
             FROM `projects` p
             LEFT JOIN `customers` c ON c.`id` = p.`customer_id`
             LEFT JOIN `users` u ON u.`id` = p.`assigned_to`
             LEFT JOIN `users` creator ON creator.`id` = p.`created_by`
             WHERE p.`id` = :id
             LIMIT 1',
            [':id' => (int) $id]
        );
        if ($project === false) {
            Response::notFound();
        }

        $projectId = (int) ($project['id'] ?? 0);
        $plans = $this->db()->fetchAll('SELECT * FROM `plans` WHERE `project_id` = :id ORDER BY `updated_at` DESC', [':id' => $projectId]);
        $documents = $this->db()->fetchAll('SELECT * FROM `documents` WHERE `project_id` = :id ORDER BY `updated_at` DESC', [':id' => $projectId]);
        $uploads = $this->db()->fetchAll('SELECT * FROM `uploads` WHERE `project_id` = :id ORDER BY `created_at` DESC', [':id' => $projectId]);
        $materials = $this->db()->fetchAll(
            'SELECT pm.*, m.`name`, m.`category`, m.`unit`
             FROM `project_materials` pm
             INNER JOIN `materials` m ON m.`id` = pm.`material_id`
             WHERE pm.`project_id` = :id
             ORDER BY m.`category`, m.`name`',
            [':id' => $projectId]
        );
        $vehicles = $this->db()->fetchAll('SELECT * FROM `vehicles` WHERE `project_id` = :id ORDER BY `created_at` DESC', [':id' => $projectId]);

        $this->render('projects/show', [
            'project' => $project,
            'plans' => $plans,
            'documents' => $documents,
            'uploads' => $uploads,
            'materials' => $materials,
            'vehicles' => $vehicles,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');

        $this->render('projects/edit', [
            'project' => $this->requireRecord('projects', $id),
            'customers' => $this->db()->fetchAll('SELECT * FROM `customers` ORDER BY `company` ASC'),
            'users' => $this->db()->fetchAll('SELECT `id`, `name` FROM `users` WHERE `is_active` = 1 ORDER BY `name` ASC'),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $project = $this->requireRecord('projects', $id);
        $input = Request::all();
        $validator = Validator::make($input, [
            'title' => 'required|max:200',
            'contact_email' => 'max:191',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $input);
            Session::flash('error', 'Please correct the project form.');
            $this->back();
        }

        $update = [
            'customer_id' => $this->nullableInt(Request::post('customer_id')),
            'assigned_to' => $this->nullableInt(Request::post('assigned_to')),
            'title' => trim((string) Request::post('title')),
            'description' => trim((string) Request::post('description', '')) ?: null,
            'location' => trim((string) Request::post('location', '')) ?: null,
            'address' => trim((string) Request::post('address', '')) ?: null,
            'gps_lat' => $this->nullableNumber(Request::post('gps_lat')),
            'gps_lng' => $this->nullableNumber(Request::post('gps_lng')),
            'contact_name' => trim((string) Request::post('contact_name', '')) ?: null,
            'contact_phone' => trim((string) Request::post('contact_phone', '')) ?: null,
            'contact_email' => trim((string) Request::post('contact_email', '')) ?: null,
            'start_date' => trim((string) Request::post('start_date', '')) ?: null,
            'end_date' => trim((string) Request::post('end_date', '')) ?: null,
            'status' => trim((string) Request::post('status', 'draft')) ?: 'draft',
            'priority' => trim((string) Request::post('priority', 'normal')) ?: 'normal',
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db()->table('projects')->where('id', (int) $id)->update($update);
        $this->recordLog('project_updated', 'projects', (int) $id, 'project', $project, $update);
        Session::flash('success', 'Project updated successfully.');
        $this->redirect('/projects/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');
        $this->ensureCsrf();

        $project = $this->requireRecord('projects', $id);
        $this->db()->table('projects')->where('id', (int) $id)->delete();
        $this->recordLog('project_deleted', 'projects', (int) $id, 'project', $project);
        Session::flash('success', 'Project deleted successfully.');
        $this->redirect('/projects');
    }

    public function upload(string $id): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $project = $this->requireRecord('projects', $id);
        $file = Request::file('file');
        if ($file === null) {
            Session::flash('error', 'Please select a file to upload.');
            $this->back();
        }

        try {
            $stored = $this->storeUploadedFile($file, 'projects/' . (int) $id);
            $this->db()->table('uploads')->insert([
                'user_id' => Auth::id(),
                'project_id' => (int) $id,
                'original_name' => $stored['original_name'],
                'stored_name' => $stored['stored_name'],
                'file_path' => $stored['file_path'],
                'file_type' => $stored['file_type'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['file_size'],
                'purpose' => trim((string) Request::post('purpose', 'attachment')) ?: 'attachment',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $uploadId = (int) $this->db()->lastInsertId();
            $this->recordLog('project_upload_added', 'projects', (int) $id, 'project', [], ['upload_id' => $uploadId]);
        } catch (\Throwable $throwable) {
            Session::flash('error', $throwable->getMessage());
            $this->back();
        }

        if (Request::isAjax()) {
            $this->json(['message' => 'Upload successful.']);
        }

        Session::flash('success', 'File uploaded successfully.');
        $this->redirect('/projects/' . (int) $project['id']);
    }

    private function generateProjectNumber(): string
    {
        $prefix = 'PRJ-' . date('Ymd');
        $count = $this->db()->fetch(
            'SELECT COUNT(*) AS `aggregate` FROM `projects` WHERE `project_number` LIKE :prefix',
            [':prefix' => $prefix . '%']
        );
        $sequence = (int) ($count['aggregate'] ?? 0) + 1;

        return sprintf('%s-%03d', $prefix, $sequence);
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableNumber(mixed $value): float|int|null
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}
