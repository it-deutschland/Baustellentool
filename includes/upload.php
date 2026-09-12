<?php

declare(strict_types=1);

function upload_store(array $file, string $subDir = '', ?array $allowedExtensions = null, ?int $maxSize = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload fehlgeschlagen.'];
    }

    $filename = (string) ($file['name'] ?? '');
    $tmpName = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $allowed = $allowedExtensions ?? (defined('ALLOWED_EXTENSIONS') ? ALLOWED_EXTENSIONS : []);
    if ($allowed !== [] && !in_array($extension, array_map('strtolower', $allowed), true)) {
        return ['ok' => false, 'message' => 'Dateityp nicht erlaubt.'];
    }

    $limit = $maxSize ?? (defined('UPLOAD_MAX_SIZE') ? (int) UPLOAD_MAX_SIZE : 10 * 1024 * 1024);
    if ($size <= 0 || $size > $limit) {
        return ['ok' => false, 'message' => 'Dateigröße ungültig oder zu groß.'];
    }

    $baseUploadPath = defined('UPLOAD_PATH') ? (string) UPLOAD_PATH : (BASE_PATH . '/dateien');
    $relativeDir = trim($subDir, '/');
    $targetDir = $relativeDir === '' ? $baseUploadPath : $baseUploadPath . '/' . $relativeDir;

    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        return ['ok' => false, 'message' => 'Zielverzeichnis konnte nicht erstellt werden.'];
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($filename, PATHINFO_FILENAME)) ?: 'file';
    $safeName = $safeBase . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
    $absolutePath = $targetDir . '/' . $safeName;

    if (!is_uploaded_file($tmpName) || !move_uploaded_file($tmpName, $absolutePath)) {
        return ['ok' => false, 'message' => 'Datei konnte nicht gespeichert werden.'];
    }

    $relativePath = 'dateien/' . ($relativeDir === '' ? '' : $relativeDir . '/') . $safeName;

    return [
        'ok' => true,
        'message' => 'Upload erfolgreich.',
        'filename' => $safeName,
        'path' => $relativePath,
        'size' => $size,
        'mime' => (string) ($file['type'] ?? ''),
    ];
}
