<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * File upload service.
 * Handles validation, storage and retrieval of uploaded files.
 *
 * @license MIT
 */
class UploadService
{
    private string $uploadPath;
    private int    $maxSize;
    private array  $allowedExtensions;

    public function __construct()
    {
        $this->uploadPath        = defined('UPLOAD_PATH')        ? UPLOAD_PATH        : BASE_PATH . '/dateien';
        $this->maxSize           = defined('UPLOAD_MAX_SIZE')    ? UPLOAD_MAX_SIZE    : 52428800;
        $this->allowedExtensions = defined('ALLOWED_EXTENSIONS') ? ALLOWED_EXTENSIONS : ['svg','png','jpg','jpeg','pdf','zip','dxf'];
    }

    // ── Handle Upload ────────────────────────────────────────

    /**
     * Process an uploaded file from $_FILES.
     *
     * @param  array  $file     $_FILES entry
     * @param  string $purpose  upload purpose (photo|attachment|drawing|symbol|…)
     * @param  string $subDir   sub-directory within uploads (e.g. 'symbols', 'projects/42')
     * @return array{stored_name:string,file_path:string,file_type:string,mime_type:string,file_size:int,original_name:string}
     * @throws \RuntimeException on validation or storage error
     */
    public function handle(array $file, string $purpose = 'other', string $subDir = ''): array
    {
        $this->validateUpload($file);

        $originalName  = $file['name'];
        $extension     = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $storedName    = $this->generateStoredName($extension);
        $dir           = $subDir !== ''
            ? $this->uploadPath . '/' . trim($subDir, '/')
            : $this->uploadPath;

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException('Upload-Verzeichnis konnte nicht erstellt werden.');
        }

        $destination = $dir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Datei konnte nicht gespeichert werden.');
        }

        // Additional SVG sanitisation
        if ($extension === 'svg') {
            $this->sanitiseSvg($destination);
        }

        $relativePath = ($subDir !== '' ? $subDir . '/' : '') . $storedName;
        $mimeType     = mime_content_type($destination) ?: 'application/octet-stream';

        return [
            'original_name' => $originalName,
            'stored_name'   => $storedName,
            'file_path'     => $relativePath,
            'file_type'     => $extension,
            'mime_type'     => $mimeType,
            'file_size'     => (int) filesize($destination),
        ];
    }

    // ── Delete ───────────────────────────────────────────────

    public function delete(string $relativePath): bool
    {
        $full = $this->uploadPath . '/' . ltrim($relativePath, '/');
        if (file_exists($full)) {
            return unlink($full);
        }
        return false;
    }

    // ── Validation ───────────────────────────────────────────

    private function validateUpload(array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload-Fehler: ' . $this->uploadErrorMessage($file['error'] ?? 0));
        }

        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new \RuntimeException('Ungültige Datei-Upload-Anfrage.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size > $this->maxSize) {
            throw new \RuntimeException(sprintf(
                'Datei ist zu groß. Maximum: %s.',
                $this->formatBytes($this->maxSize)
            ));
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions, true)) {
            throw new \RuntimeException(sprintf(
                'Dateityp „%s" ist nicht erlaubt. Erlaubt: %s',
                $ext,
                implode(', ', $this->allowedExtensions)
            ));
        }
    }

    private function sanitiseSvg(string $path): void
    {
        $content = file_get_contents($path);
        if ($content === false) return;

        // Remove potentially dangerous elements/attributes
        $dangerous = ['<script', 'javascript:', 'onload=', 'onerror=', 'onclick=', 'onmouseover='];
        foreach ($dangerous as $pattern) {
            if (stripos($content, $pattern) !== false) {
                // Replace entire script sections
                $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $content);
                $content = preg_replace('/\bon\w+\s*=/i', 'data-removed=', $content);
                break;
            }
        }

        file_put_contents($path, $content);
    }

    private function generateStoredName(string $extension): string
    {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Datei überschreitet die maximale Upload-Größe.',
            UPLOAD_ERR_PARTIAL   => 'Datei wurde nur teilweise hochgeladen.',
            UPLOAD_ERR_NO_FILE   => 'Keine Datei ausgewählt.',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporäres Verzeichnis fehlt.',
            UPLOAD_ERR_CANT_WRITE => 'Datei konnte nicht auf Disk geschrieben werden.',
            UPLOAD_ERR_EXTENSION  => 'Upload durch PHP-Extension gestoppt.',
            default               => 'Unbekannter Fehler (Code ' . $code . ').',
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 0) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 0) . ' KB';
        return $bytes . ' B';
    }
}
