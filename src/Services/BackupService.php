<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use PDO;

/**
 * Backup & Restore service.
 * Creates SQL dumps and ZIP archives of the upload directory.
 *
 * @license MIT
 */
class BackupService
{
    private string $backupPath;
    private string $uploadPath;

    public function __construct()
    {
        $this->backupPath = defined('BACKUP_PATH') ? BACKUP_PATH : BASE_PATH . '/sicherungen';
        $this->uploadPath = defined('UPLOAD_PATH')  ? UPLOAD_PATH  : BASE_PATH . '/dateien';
    }

    // ── Create ───────────────────────────────────────────────

    /**
     * Create a full backup (SQL dump + uploads ZIP).
     * Returns the backup filename (without path) or null on failure.
     */
    public function create(): ?string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $name      = "backup_{$timestamp}";

        try {
            $sqlFile = $this->dumpDatabase($name);
            $zipFile = $this->createZip($name, $sqlFile);

            // Remove the standalone SQL file after zipping
            if ($sqlFile && file_exists($sqlFile)) {
                @unlink($sqlFile);
            }

            Logger::info('Backup created.', ['file' => basename($zipFile)]);
            return basename($zipFile);
        } catch (\Throwable $e) {
            Logger::error('Backup creation failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ── List ─────────────────────────────────────────────────

    /**
     * List all backup files with metadata.
     *
     * @return array<array{name:string,size:int,size_human:string,date:string,path:string}>
     */
    public function list(): array
    {
        $files = glob($this->backupPath . '/backup_*.zip') ?: [];
        $result = [];
        foreach ($files as $file) {
            $size = filesize($file);
            $result[] = [
                'name'       => basename($file),
                'size'       => $size,
                'size_human' => $this->formatBytes($size),
                'date'       => date('d.m.Y H:i', (int) filemtime($file)),
                'path'       => $file,
            ];
        }
        usort($result, static fn ($a, $b) => strcmp($b['date'], $a['date']));
        return $result;
    }

    // ── Restore ──────────────────────────────────────────────

    /**
     * Restore the database from a ZIP backup file.
     */
    public function restore(string $filename): bool
    {
        $filename = basename($filename); // prevent traversal
        $zipPath  = $this->backupPath . '/' . $filename;

        if (!file_exists($zipPath)) {
            Logger::error('Backup file not found.', ['file' => $filename]);
            return false;
        }

        if (!extension_loaded('zip')) {
            Logger::error('ZIP extension not available for restore.');
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        // Find SQL file inside
        $sqlFile = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with((string) $name, '.sql')) {
                $sqlFile = $name;
                break;
            }
        }

        if (!$sqlFile) {
            $zip->close();
            Logger::error('No SQL file found in backup.', ['file' => $filename]);
            return false;
        }

        $sql = $zip->getFromName($sqlFile);
        $zip->close();

        if ($sql === false) {
            return false;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME
            );
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if ($stmt !== '') {
                    $pdo->exec($stmt);
                }
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            Logger::info('Backup restored.', ['file' => $filename]);
            return true;
        } catch (\Throwable $e) {
            Logger::error('Restore failed.', ['error' => $e->getMessage()]);
            return false;
        }
    }

    // ── Delete ───────────────────────────────────────────────

    public function delete(string $filename): bool
    {
        $filename = basename($filename);
        $path     = $this->backupPath . '/' . $filename;
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    // ── Internals ────────────────────────────────────────────

    private function dumpDatabase(string $name): string
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $out  = "-- Sonka Bau & Sonnenimmobilien - Multi Administration Database Backup\n";
        $out .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $out .= "-- Database: " . DB_NAME . "\n\n";
        $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            // DROP + CREATE
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $out .= $create[1] . ";\n\n";

            // Data
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $cols   = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $out   .= "INSERT INTO `{$table}` ({$cols}) VALUES\n";
                $values = [];
                foreach ($rows as $row) {
                    $escaped = array_map(static function ($v) use ($pdo): string {
                        if ($v === null) return 'NULL';
                        return $pdo->quote((string) $v);
                    }, $row);
                    $values[] = '(' . implode(', ', $escaped) . ')';
                }
                $out .= implode(",\n", $values) . ";\n\n";
            }
        }

        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $sqlPath = $this->backupPath . "/{$name}.sql";
        file_put_contents($sqlPath, $out);
        return $sqlPath;
    }

    private function createZip(string $name, string $sqlFile): string
    {
        $zipPath = $this->backupPath . "/{$name}.zip";

        if (!extension_loaded('zip')) {
            // Fallback: just return the SQL file path
            rename($sqlFile, $this->backupPath . "/{$name}.sql");
            return $this->backupPath . "/{$name}.sql";
        }

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($sqlFile, basename($sqlFile));

        // Add uploads (skip if too large or unavailable)
        if (is_dir($this->uploadPath)) {
            $this->addDirToZip($zip, $this->uploadPath, 'uploads');
        }

        $zip->close();
        return $zipPath;
    }

    private function addDirToZip(\ZipArchive $zip, string $dir, string $zipDir): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isFile()) continue;
            $realPath  = $file->getRealPath();
            $relative  = $zipDir . '/' . str_replace($dir . DIRECTORY_SEPARATOR, '', $realPath);
            $zip->addFile($realPath, $relative);
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
