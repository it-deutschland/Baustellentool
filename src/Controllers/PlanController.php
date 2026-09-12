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
 * Handles plan management and export.
 */
class PlanController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();

        $plans = $this->db()->fetchAll(
            'SELECT pl.*, p.`title` AS `project_title`, p.`project_number`
             FROM `plans` pl
             INNER JOIN `projects` p ON p.`id` = pl.`project_id`
             ORDER BY pl.`updated_at` DESC'
        );

        $this->render('plans/index', ['plans' => $plans]);
    }

    public function create(string $pid): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');

        $this->render('plans/create', [
            'project' => $this->requireRecord('projects', $pid),
        ]);
    }

    public function store(string $pid): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $project = $this->requireRecord('projects', $pid);
        $validator = Validator::make(Request::all(), [
            'title' => 'required|max:200',
            'scale' => 'required|max:20',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the plan form.');
            $this->back();
        }

        $payload = [
            'project_id' => (int) $project['id'],
            'created_by' => Auth::id(),
            'title' => trim((string) Request::post('title')),
            'description' => trim((string) Request::post('description', '')) ?: null,
            'scale' => trim((string) Request::post('scale', '1:500')) ?: '1:500',
            'canvas_data' => json_encode(Request::post('canvas_data', ['objects' => []]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => trim((string) Request::post('status', 'draft')) ?: 'draft',
            'version' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db()->table('plans')->insert($payload);
        $planId = (int) $this->db()->lastInsertId();
        $this->recordLog('plan_created', 'plans', $planId, 'plan', [], $payload);
        Session::flash('success', 'Plan created successfully.');
        $this->redirect('/plans/' . $planId . '/editor');
    }

    public function editor(string $id): void
    {
        $this->requireAuth();

        $plan = $this->db()->fetch(
            'SELECT pl.*, p.`title` AS `project_title`, p.`project_number`
             FROM `plans` pl
             INNER JOIN `projects` p ON p.`id` = pl.`project_id`
             WHERE pl.`id` = :id
             LIMIT 1',
            [':id' => (int) $id]
        );
        if ($plan === false) {
            Response::notFound();
        }

        $this->render('plans/editor', ['plan' => $plan], 'none');
    }

    public function save(string $id): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $plan = $this->requireRecord('plans', $id);
        $canvasData = Request::input('canvas_data');
        $thumbnailData = (string) Request::input('thumbnail', '');
        $update = [
            'canvas_data' => is_string($canvasData) ? $canvasData : json_encode($canvasData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (str_starts_with($thumbnailData, 'data:image/')) {
            $thumbnailPath = $this->storeThumbnail((int) $id, $thumbnailData);
            $update['thumbnail'] = $thumbnailPath;
        }

        $this->db()->table('plans')->where('id', (int) $id)->update($update);
        $this->recordLog('plan_saved', 'plans', (int) $id, 'plan', $plan, $update);
        $this->json(['message' => 'Plan saved successfully.', 'plan_id' => (int) $id]);
    }

    public function exportForm(string $id): void
    {
        $this->requireAuth();

        $this->render('plans/export', ['plan' => $this->requireRecord('plans', $id)]);
    }

    public function export(string $id): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $plan = $this->requireRecord('plans', $id);
        $format = strtolower((string) Request::post('format', 'svg'));
        $svg = $this->buildPlanSvg($plan);

        if ($format === 'svg') {
            Response::setHeader('Content-Type', 'image/svg+xml; charset=UTF-8');
            Response::setHeader('Content-Disposition', 'attachment; filename="plan-' . (int) $id . '.svg"');
            echo $svg;
            exit;
        }

        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);
        $payload = [
            'format' => $format,
            'data_uri' => $dataUri,
            'filename' => 'plan-' . (int) $id . '.' . $format,
        ];
        $this->recordLog('plan_exported', 'plans', (int) $id, 'plan', [], ['format' => $format]);

        if (Request::isAjax()) {
            $this->json($payload);
        }

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Plan Export</title></head><body>'
            . '<p>Use the generated data URI below to create the requested export.</p>'
            . '<textarea style="width:100%;height:240px;">' . htmlspecialchars($dataUri, ENT_QUOTES, 'UTF-8') . '</textarea>'
            . '<img src="' . htmlspecialchars($dataUri, ENT_QUOTES, 'UTF-8') . '" alt="Plan export" style="max-width:100%;display:block;margin-top:16px;">'
            . '</body></html>';
        $this->renderHtml($html);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();

        $plan = $this->db()->fetch(
            'SELECT pl.*, p.`title` AS `project_title`
             FROM `plans` pl
             INNER JOIN `projects` p ON p.`id` = pl.`project_id`
             WHERE pl.`id` = :id
             LIMIT 1',
            [':id' => (int) $id]
        );
        if ($plan === false) {
            Response::notFound();
        }

        $this->render('plans/edit', ['plan' => $plan]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $plan = $this->requireRecord('plans', $id);
        $validator = Validator::make(Request::all(), [
            'title' => 'required|max:200',
            'scale' => 'required|max:20',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the plan form.');
            $this->back();
        }

        $update = [
            'title' => trim((string) Request::post('title')),
            'description' => trim((string) Request::post('description', '')) ?: null,
            'scale' => trim((string) Request::post('scale', '1:500')) ?: '1:500',
            'status' => trim((string) Request::post('status', 'draft')) ?: 'draft',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db()->table('plans')->where('id', (int) $id)->update($update);
        $this->recordLog('plan_updated', 'plans', (int) $id, 'plan', $plan, $update);
        Session::flash('success', 'Plan updated successfully.');
        $this->redirect('/plans/' . (int) $id . '/edit');
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $plan = $this->requireRecord('plans', $id);
        $this->db()->table('plans')->where('id', (int) $id)->delete();
        $this->recordLog('plan_deleted', 'plans', (int) $id, 'plan', $plan);
        Session::flash('success', 'Plan deleted successfully.');
        $this->redirect('/projects/' . (int) $plan['project_id']);
    }

    private function storeThumbnail(int $planId, string $dataUri): string
    {
        $matches = [];
        if (preg_match('/^data:image\/(png|jpeg|jpg);base64,(.+)$/', $dataUri, $matches) !== 1) {
            return '';
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $binary = base64_decode($matches[2], true);
        if ($binary === false) {
            return '';
        }

        $directory = rtrim((defined('UPLOAD_PATH') ? (string) UPLOAD_PATH : BASE_PATH . '/dateien'), '/') . '/plans/thumbnails';
        $this->ensureDirectory($directory);
        $fileName = 'plan_' . $planId . '_' . date('YmdHis') . '.' . $extension;
        file_put_contents($directory . '/' . $fileName, $binary);

        return '/dateien/plans/thumbnails/' . $fileName;
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function buildPlanSvg(array $plan): string
    {
        $title = htmlspecialchars((string) ($plan['title'] ?? 'Plan'), ENT_QUOTES, 'UTF-8');
        $canvasData = htmlspecialchars((string) ($plan['canvas_data'] ?? '{}'), ENT_QUOTES, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900" viewBox="0 0 1600 900">'
            . '<rect width="1600" height="900" fill="#ffffff"/>'
            . '<text x="60" y="90" font-size="42" font-family="Arial, sans-serif" fill="#222">' . $title . '</text>'
            . '<foreignObject x="60" y="130" width="1480" height="710">'
            . '<div xmlns="http://www.w3.org/1999/xhtml" style="font-family:monospace;font-size:16px;white-space:pre-wrap;color:#444;border:1px solid #ddd;padding:24px;">'
            . $canvasData
            . '</div></foreignObject></svg>';
    }
}
