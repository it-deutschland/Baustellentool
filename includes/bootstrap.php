<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('SRC_PATH')) {
    define('SRC_PATH', BASE_PATH . '/src');
}
if (!defined('VIEWS_PATH')) {
    define('VIEWS_PATH', SRC_PATH . '/Views');
}

if (!is_file(BASE_PATH . '/config.php')) {
    http_response_code(503);
    echo '<h1>Konfiguration fehlt</h1><p>Bitte eine <code>config.php</code> anlegen und Datenbank importieren.</p>';
    exit;
}

require_once BASE_PATH . '/config.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = SRC_PATH . '/';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/helpers.php';
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/includes/upload.php';
require_once BASE_PATH . '/includes/controller_runner.php';

date_default_timezone_set((string) APP_TIMEZONE);

\App\Core\Session::start();
\App\Core\Database::getInstance()->connect();
