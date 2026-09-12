<?php
/**
 * Authentifizierung über PHP-Sessions.
 * Kein JWT, kein Framework – nur $_SESSION.
 */
declare(strict_types=1);

function auth_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure   = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
    $lifetime = defined('SESSION_LIFETIME') ? (int) SESSION_LIFETIME : 7200;
    ini_set('session.use_strict_mode',  '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly',  '1');
    ini_set('session.cookie_secure',    $secure ? '1' : '0');
    ini_set('session.gc_maxlifetime',   (string) max(1440, $lifetime));
    if (defined('SESSION_NAME') && SESSION_NAME !== '') {
        session_name(SESSION_NAME);
    }
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/** Gibt den eingeloggten Nutzer als Array zurück (oder null). */
function auth_user(): ?array {
    auth_start();
    $u = $_SESSION['auth_user'] ?? null;
    return is_array($u) ? $u : null;
}

/** True wenn jemand eingeloggt ist. */
function auth_check(): bool {
    return auth_user() !== null;
}

/** ID des eingeloggten Nutzers. */
function auth_id(): ?int {
    $u = auth_user();
    return $u ? (int) $u['id'] : null;
}

/** Rolle des eingeloggten Nutzers (admin / editor / reviewer / guest). */
function auth_role(): string {
    $u = auth_user();
    return strtolower((string) ($u['role'] ?? 'guest'));
}

/** True wenn Nutzer mindestens die angegebene Rolle hat. */
function auth_has_role(string $role): bool {
    $hierarchy = ['guest' => 0, 'reviewer' => 1, 'editor' => 2, 'admin' => 3];
    $userLevel = $hierarchy[auth_role()] ?? 0;
    $needed    = $hierarchy[strtolower($role)] ?? 99;
    return $userLevel >= $needed;
}

/** Einloggen: User-Array in Session speichern. */
function auth_login(array $user): void {
    auth_start();
    session_regenerate_id(true);
    $_SESSION['auth_user'] = $user;
}

/** Ausloggen. */
function auth_logout(): void {
    auth_start();
    unset($_SESSION['auth_user']);
    session_regenerate_id(true);
}

/** Weiterleitung zur Login-Seite wenn nicht eingeloggt. */
function require_auth(): void {
    if (!auth_check()) {
        flash('error', 'Bitte melden Sie sich an.');
        redirect('/login');
    }
}

/** Weiterleitung mit 403 wenn Rolle nicht ausreicht. */
function require_role(string $role): void {
    require_auth();
    if (!auth_has_role($role)) {
        http_response_code(403);
        die('<h1>403 – Keine Berechtigung</h1>');
    }
}

/** Versucht Login: prüft Passwort, gibt User-Array zurück oder false. */
function auth_attempt(string $email, string $password): array|false {
    $user = db_row(
        'SELECT u.*, r.name AS role FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.email = ? LIMIT 1',
        [strtolower(trim($email))]
    );
    if (!$user) return false;
    if (!(int)($user['is_active'] ?? 1)) return false;
    if (!password_verify($password, (string)($user['password'] ?? ''))) return false;
    return $user;
}
