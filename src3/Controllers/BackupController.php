<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Handles backup creation and restore.
 */
class BackupController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');

        $backupPath = defined('BACKUP_PATH') ? (string) BACKUP_PATH : BASE_PATH . '/sicherungen';
        $this->ensureDirectory($backupPath);
        $files = [];
        foreach (scandir($backupPath) ?: [] as $file) {
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }
            $fullPath = $backupPath . '/' . $file;
            if (is_file($fullPath)) {
                $files[] = [
                    'name' => $file,
                    'size' => filesize($fullPath) ?: 0,
                    'modified_at' => date('Y-m-d H:i:s', filemtime($fullPath) ?: time()),
                ];
            }
        }
        usort($files, static fn (array $left, array $right): int => strcmp((string) $right['modified_at'], (string) $left['modified_at']));

        $this->render('backup/index', ['files' => $files]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');
        $this->ensureCsrf();

        if (class_exists('App\\Services\\BackupService')) {
            $service = new \App\Services\BackupService();
            if (method_exists($service, 'create')) {
                $service->create();
                Session::flash('success', 'Backup created successfully.');
                $this->redirect('/backup');
            }
        }

        $backupPath = defined('BACKUP_PATH') ? (string) BACKUP_PATH : BASE_PATH . '/sicherungen';
        $this->ensureDirectory($backupPath);
        $baseName = 'backup-' . date('Ymd-His');
        $sqlFile = $backupPath . '/' . $baseName . '.sql';
        $zipFile = $backupPath . '/' . $baseName . '-uploads.zip';

        file_put_contents($sqlFile, $this->buildSqlDump());
        $this->createUploadsArchive($zipFile);

        $this->recordLog('backup_created', 'backup', null, null, [], ['sql' => basename($sqlFile), 'uploads' => basename($zipFile)]);
        Session::flash('success', 'Backup created successfully.');
        $this->redirect('/backup');
    }

    public function download(string $f): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');

        $fileName = $this->sanitizeBackupName($f);
        $fullPath = (defined('BACKUP_PATH') ? (string) BACKUP_PATH : BASE_PATH . '/sicherungen') . '/' . $fileName;
        if (!is_file($fullPath)) {
            Response::notFound();
        }

        Response::setHeader('Content-Type', 'application/octet-stream');
        Response::setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        Response::setHeader('Content-Length', (string) filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    public function restore(): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');
        $this->ensureCsrf();

        $file = Request::file('backup_file');
        if ($file === null) {
            Session::flash('error', 'Please choose an SQL backup file.');
            $this->back();
        }

        $stored = $this->storeUploadedFile($file, 'sicherungen/imports', ['sql']);
        $sql = file_get_contents((string) $stored['absolute_path']);
        if (!is_string($sql) || trim($sql) === '') {
            Session::flash('error', 'The selected backup file is empty.');
            $this->back();
        }

        $pdo = $this->db()->connect();
        $pdo->beginTransaction();
        try {
            foreach ($this->splitSqlStatements($sql) as $statement) {
                if (trim($statement) === '') {
                    continue;
                }
                $pdo->exec($statement);
            }
            $pdo->commit();
        } catch (\Throwable $throwable) {
            $pdo->rollBack();
            Session::flash('error', 'Restore failed: ' . $throwable->getMessage());
            $this->back();
        }

        $this->recordLog('backup_restored', 'backup', null, null, [], ['file' => $stored['file_path']]);
        Session::flash('success', 'Backup restored successfully.');
        $this->redirect('/backup');
    }

    public function delete(string $f): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');
        $this->ensureCsrf();

        $fileName = $this->sanitizeBackupName($f);
        $fullPath = (defined('BACKUP_PATH') ? (string) BACKUP_PATH : BASE_PATH . '/sicherungen') . '/' . $fileName;
        if (!is_file($fullPath)) {
            Response::notFound();
        }

        unlink($fullPath);
        $this->recordLog('backup_deleted', 'backup', null, null, [], ['file' => $fileName]);
        Session::flash('success', 'Backup deleted successfully.');
        $this->redirect('/backup');
    }

    private function buildSqlDump(): string
    {
        $pdo = $this->db()->connect();
        $lines = ['-- Sonka Bau & Sonnenimmobilien - Multi Administration backup', '-- Generated at ' . date('c'), 'SET FOREIGN_KEY_CHECKS = 0;', ''];
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $tableName = (string) $table;
            $create = $pdo->query('SHOW CREATE TABLE `' . $tableName . '`')->fetch(PDO::FETCH_ASSOC);
            $createSql = $create['Create Table'] ?? $create['Create View'] ?? '';
            $lines[] = 'DROP TABLE IF EXISTS `' . $tableName . '`;';
            $lines[] = (string) $createSql . ';';
            $rows = $pdo->query('SELECT * FROM `' . $tableName . '`')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $columns = array_map(static fn (string $column): string => '`' . $column . '`', array_keys($row));
                $values = array_map(static fn (mixed $value): string => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($row));
                $lines[] = 'INSERT INTO `' . $tableName . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
            }
            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function createUploadsArchive(string $zipFile): void
    {
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $uploadPath = defined('UPLOAD_PATH') ? (string) UPLOAD_PATH : BASE_PATH . '/dateien';
        if (is_dir($uploadPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadPath, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $fullPath = $item->getPathname();
                    $relativePath = ltrim(str_replace($uploadPath, '', $fullPath), '/');
                    $zip->addFile($fullPath, $relativePath);
                }
            }
        }
        $zip->close();
    }

    /**
     * Sanitise a backup filename: strip path traversal, keep only safe chars.
     * Returns only the basename with .zip or .sql extension.
     */
    private function sanitizeBackupName(string $filename): string
    {
        // basename strips any directory component
        $name = basename($filename);
        // Allow only alphanumeric, underscores, hyphens and dots
        $name = (string) preg_replace('/[^a-zA-Z0-9_\-.]/', '', $name);
        // Must end in .zip or .sql
        if (!preg_match('/\.(zip|sql)$/i', $name)) {
            $name = '';
        }
        return $name;
    }

    /**
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $length = strlen($sql);

        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            $prev = $index > 0 ? $sql[$index - 1] : '';
            if ($char === "'" && $prev !== '\\') {
                $inString = !$inString;
            }
            if ($char === ';' && !$inString) {
                $statements[] = trim($buffer);
                $buffer = '';
                continue;
            }
            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        return $statements;
    }
}
