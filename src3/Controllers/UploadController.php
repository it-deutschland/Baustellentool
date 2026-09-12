<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Handles generic upload endpoints.
 */
class UploadController extends Controller
{
    use ControllerHelpers;

    public function store(): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $file = Request::file('file');
        if ($file === null) {
            $this->json(['message' => 'No file uploaded.'], 422);
        }

        try {
            $purpose = trim((string) Request::post('purpose', 'other')) ?: 'other';
            $stored = $this->storeUploadedFile($file, 'generic/' . $purpose);
            $this->db()->table('uploads')->insert([
                'user_id' => Auth::id(),
                'project_id' => $this->nullableInt(Request::post('project_id')),
                'plan_id' => $this->nullableInt(Request::post('plan_id')),
                'document_id' => $this->nullableInt(Request::post('document_id')),
                'original_name' => $stored['original_name'],
                'stored_name' => $stored['stored_name'],
                'file_path' => $stored['file_path'],
                'file_type' => $stored['file_type'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['file_size'],
                'purpose' => $purpose,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $uploadId = (int) $this->db()->lastInsertId();
            $this->recordLog('upload_created', 'uploads', $uploadId, 'upload');

            $this->json([
                'message' => 'Upload successful.',
                'id' => $uploadId,
                'url' => '/dateien/' . $uploadId,
            ]);
        } catch (\Throwable $throwable) {
            $this->json(['message' => $throwable->getMessage()], 422);
        }
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $upload = $this->requireRecord('uploads', $id);
        if ((int) ($upload['user_id'] ?? 0) !== (int) Auth::id() && !Auth::hasRole('admin')) {
            $this->json(['message' => 'Forbidden.'], 403);
        }

        $path = $this->resolveStoragePath((string) $upload['file_path']);
        if (is_file($path)) {
            unlink($path);
        }
        $this->db()->table('uploads')->where('id', (int) $id)->delete();
        $this->recordLog('upload_deleted', 'uploads', (int) $id, 'upload', $upload);

        if (Request::isAjax()) {
            $this->json(['message' => 'Upload deleted successfully.']);
        }

        Session::flash('success', 'Upload deleted successfully.');
        $this->back();
    }

    public function serve(string $id): void
    {
        $this->requireAuth();

        $upload = $this->requireRecord('uploads', $id);
        $path = $this->resolveStoragePath((string) $upload['file_path']);
        if (!is_file($path)) {
            Response::notFound();
        }

        Response::setHeader('Content-Type', (string) ($upload['mime_type'] ?? 'application/octet-stream'));
        Response::setHeader('Content-Length', (string) filesize($path));
        Response::setHeader('Content-Disposition', 'inline; filename="' . basename((string) ($upload['original_name'] ?? basename($path))) . '"');
        readfile($path);
        exit;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
