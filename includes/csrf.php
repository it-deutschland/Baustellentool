<?php
/**
 * CSRF-Schutz – zwei Funktionen genügen.
 */
declare(strict_types=1);

/** Gibt das aktuelle CSRF-Token zurück (erzeugt es bei Bedarf). */
function csrf_token(): string {
    auth_start();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Gibt ein verstecktes HTML-Input-Feld mit dem CSRF-Token zurück. */
function csrf_field(): string {
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/** Prüft das Token; bricht mit 403 ab wenn ungültig. */
function csrf_verify(): void {
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'], true)) {
        return;
    }
    $token = $_POST['_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? '';
    if (!hash_equals(csrf_token(), (string)$token)) {
        http_response_code(403);
        die('Ungültiger CSRF-Token.');
    }
}
