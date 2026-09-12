<?php
/**
 * Allgemeine Hilfsfunktionen.
 */
declare(strict_types=1);

/** HTML-Escape – immer verwenden in Templates. */
function e(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Weiterleitung. */
function redirect(string $url): never {
    header('Location: ' . $url, true, 302);
    exit;
}

/** Flash-Nachricht setzen. */
function flash(string $type, string $msg): void {
    auth_start();
    $_SESSION['_flash'][$type] = $msg;
}

/** Flash-Nachricht lesen und löschen. */
function flash_get(string $type): ?string {
    auth_start();
    $msg = $_SESSION['_flash'][$type] ?? null;
    unset($_SESSION['_flash'][$type]);
    return is_string($msg) ? $msg : null;
}

/** Alle Flash-Typen abrufen. */
function flash_all(): array {
    $out = [];
    foreach (['success','error','warning','info'] as $type) {
        $msg = flash_get($type);
        if ($msg !== null) $out[$type] = $msg;
    }
    return $out;
}

/** Datum formatieren. */
function fmt_date(?string $value, string $format = 'd.m.Y H:i'): string {
    if (!$value || trim($value) === '') return '—';
    $ts = strtotime($value);
    return $ts !== false ? date($format, $ts) : e($value);
}

/** Dateigröße formatieren. */
function fmt_size(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

/** Währung formatieren. */
function fmt_money(?float $value): string {
    if ($value === null) return '—';
    return number_format($value, 2, ',', '.') . ' €';
}

/** Paginierung: gibt HTML-Navlinks zurück. */
function paginate_links(int $total, int $perPage, int $currentPage, string $baseUrl): string {
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) return '';
    $html = '<nav aria-label="Seitennavigation"><ul class="pagination">';
    $sep  = str_contains($baseUrl, '?') ? '&' : '?';
    for ($p = 1; $p <= $pages; $p++) {
        $active = $p === $currentPage ? ' active' : '';
        $html .= '<li class="page-item' . $active . '">'
               . '<a class="page-link" href="' . e($baseUrl . $sep . 'page=' . $p) . '">' . $p . '</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

/** Gibt einen einzeiligen Fehlertext aus dem Formular-Kontext zurück. */
function old(string $key, string $default = ''): string {
    auth_start();
    return e((string) ($_SESSION['_old'][$key] ?? $default));
}

/** Speichert alte Eingaben für Redirect-after-Error. */
function save_old(array $data): void {
    auth_start();
    $safe = [];
    foreach ($data as $k => $v) {
        if (str_contains(strtolower($k), 'password')) continue; // Passwörter nie speichern
        $safe[$k] = $v;
    }
    $_SESSION['_old'] = $safe;
}

/** Löscht alte Eingaben. */
function clear_old(): void {
    auth_start();
    unset($_SESSION['_old']);
}

/** Gibt den Wert einer Setting-Zeile zurück. */
function setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $rows = db_all('SELECT `key`, `value` FROM settings');
        $cache = [];
        foreach ($rows as $row) $cache[$row['key']] = (string) $row['value'];
    }
    return $cache[$key] ?? $default;
}

/** Gibt die aktuelle URL zurück. */
function current_url(): string {
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}

/** Schreibt einen Eintrag in die logs-Tabelle. */
function log_action(string $action, string $module, ?int $subjectId = null): void {
    try {
        db_run(
            'INSERT INTO logs (user_id, action, module, subject_id, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [auth_id(), $action, $module, $subjectId, $_SERVER['REMOTE_ADDR'] ?? '']
        );
    } catch (\Throwable) {}
}

/** Eindeutige Projektnummer generieren. */
function generate_project_number(): string {
    $prefix = 'PRJ-' . date('Ymd');
    $count  = (int) (db_row('SELECT COUNT(*) as c FROM projects WHERE project_number LIKE ?', [$prefix . '%'])['c'] ?? 0);
    return $prefix . '-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
}
