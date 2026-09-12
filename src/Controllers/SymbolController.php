<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Manages symbol libraries.
 */
class SymbolController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();

        $search = trim((string) Request::get('search', ''));
        $category = trim((string) Request::get('category', ''));
        $params = [];
        $where = ['1 = 1'];
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
        $grouped = [];
        foreach ($symbols as $symbol) {
            $grouped[(string) $symbol['category']][] = $symbol;
        }

        $this->render('symbols/index', [
            'symbols' => $grouped,
            'search' => $search,
            'category' => $category,
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->render('symbols/create');
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $validator = Validator::make(Request::all(), [
            'category' => 'required|max:100',
            'name' => 'required|max:200',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the symbol form.');
            $this->back();
        }

        $file = Request::file('file');
        if ($file === null) {
            Session::flash('error', 'Please upload an SVG or PNG file.');
            $this->back();
        }

        $stored = $this->storeUploadedFile($file, 'symbols', ['svg', 'png', 'jpg', 'jpeg']);
        $payload = $this->symbolPayload();
        $payload['file_path'] = $stored['file_path'];
        $payload['file_type'] = $stored['file_type'] === 'jpeg' ? 'jpg' : $stored['file_type'];
        $payload['created_by'] = Auth::id();
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db()->table('symbols')->insert($payload);
        $symbolId = (int) $this->db()->lastInsertId();
        $this->recordLog('symbol_created', 'symbols', $symbolId, 'symbol', [], $payload);
        Session::flash('success', 'Symbol created successfully.');
        $this->redirect('/symbols');
    }

    public function import(): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $archive = Request::file('archive');
        if ($archive === null) {
            Session::flash('error', 'Please upload a ZIP archive.');
            $this->back();
        }

        $storedArchive = $this->storeUploadedFile($archive, 'imports', ['zip']);
        $importRoot = rtrim((defined('STORAGE_PATH') ? (string) STORAGE_PATH : BASE_PATH . '/speicher'), '/') . '/symbol-imports/' . bin2hex(random_bytes(6));
        $this->ensureDirectory($importRoot);

        $zip = new ZipArchive();
        if ($zip->open($storedArchive['absolute_path']) !== true) {
            Session::flash('error', 'Unable to open the uploaded ZIP archive.');
            $this->back();
        }
        $zip->extractTo($importRoot);
        $zip->close();

        $category = trim((string) Request::post('category', 'Imported')) ?: 'Imported';
        $imported = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($importRoot, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $extension = strtolower($fileInfo->getExtension());
            if (!in_array($extension, ['svg', 'png', 'jpg', 'jpeg'], true)) {
                continue;
            }

            $sourcePath = $fileInfo->getPathname();
            $destinationDirectory = rtrim((defined('UPLOAD_PATH') ? (string) UPLOAD_PATH : BASE_PATH . '/dateien'), '/') . '/symbols/imported';
            $this->ensureDirectory($destinationDirectory);
            $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
            $destinationPath = $destinationDirectory . '/' . $storedName;
            copy($sourcePath, $destinationPath);

            $this->db()->table('symbols')->insert([
                'category' => $category,
                'subcategory' => trim((string) Request::post('subcategory', '')) ?: null,
                'name' => pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME),
                'sign_number' => null,
                'description' => null,
                'file_path' => '/dateien/symbols/imported/' . $storedName,
                'file_type' => $extension === 'jpeg' ? 'jpg' : $extension,
                'width_mm' => null,
                'height_mm' => null,
                'tags' => null,
                'is_favourite' => 0,
                'license' => null,
                'source' => 'ZIP import',
                'created_by' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $imported++;
        }

        $this->deleteDirectory($importRoot);
        $this->recordLog('symbols_imported', 'symbols', null, 'symbol', [], ['count' => $imported, 'archive' => $storedArchive['file_path']]);
        Session::flash('success', sprintf('%d symbols imported successfully.', $imported));
        $this->redirect('/symbols');
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->render('symbols/edit', ['symbol' => $this->requireRecord('symbols', $id)]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $symbol = $this->requireRecord('symbols', $id);
        $validator = Validator::make(Request::all(), [
            'category' => 'required|max:100',
            'name' => 'required|max:200',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the symbol form.');
            $this->back();
        }

        $update = $this->symbolPayload();
        $file = Request::file('file');
        if ($file !== null && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $stored = $this->storeUploadedFile($file, 'symbols', ['svg', 'png', 'jpg', 'jpeg']);
            $update['file_path'] = $stored['file_path'];
            $update['file_type'] = $stored['file_type'] === 'jpeg' ? 'jpg' : $stored['file_type'];
        }
        $this->db()->table('symbols')->where('id', (int) $id)->update($update);
        $this->recordLog('symbol_updated', 'symbols', (int) $id, 'symbol', $symbol, $update);
        Session::flash('success', 'Symbol updated successfully.');
        $this->redirect('/symbols');
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $symbol = $this->requireRecord('symbols', $id);
        $this->db()->table('symbols')->where('id', (int) $id)->delete();
        $this->recordLog('symbol_deleted', 'symbols', (int) $id, 'symbol', $symbol);
        Session::flash('success', 'Symbol deleted successfully.');
        $this->redirect('/symbols');
    }

    public function toggleFavourite(string $id): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $symbol = $this->requireRecord('symbols', $id);
        $value = (int) ($symbol['is_favourite'] ?? 0) === 1 ? 0 : 1;
        $this->db()->table('symbols')->where('id', (int) $id)->update(['is_favourite' => $value]);
        $this->recordLog('symbol_favourite_toggled', 'symbols', (int) $id, 'symbol', ['is_favourite' => $symbol['is_favourite'] ?? 0], ['is_favourite' => $value]);
        $this->json(['id' => (int) $id, 'is_favourite' => $value]);
    }

    /**
     * @return array<string, mixed>
     */
    private function symbolPayload(): array
    {
        return [
            'category' => trim((string) Request::post('category')),
            'subcategory' => trim((string) Request::post('subcategory', '')) ?: null,
            'name' => trim((string) Request::post('name')),
            'sign_number' => trim((string) Request::post('sign_number', '')) ?: null,
            'description' => trim((string) Request::post('description', '')) ?: null,
            'width_mm' => Request::post('width_mm') === '' ? null : (int) Request::post('width_mm', 0),
            'height_mm' => Request::post('height_mm') === '' ? null : (int) Request::post('height_mm', 0),
            'tags' => trim((string) Request::post('tags', '')) ?: null,
            'is_favourite' => $this->normalizeBoolean(Request::post('is_favourite', 0)),
            'license' => trim((string) Request::post('license', '')) ?: null,
            'source' => trim((string) Request::post('source', '')) ?: null,
        ];
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
