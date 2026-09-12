<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);
define('SRC_PATH', BASE_PATH . '/src');
define('VIEWS_PATH', SRC_PATH . '/Views');

require_once BASE_PATH . '/includes/bootstrap.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$uri = rawurldecode((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH));
$uri = rtrim($uri, '/');
$uri = $uri === '' ? '/' : $uri;

$routes = [
    ['GET', '/', ['page' => '/pages/dashboard.php']],
    ['GET', '/dashboard', ['page' => '/pages/dashboard.php']],
    ['GET', '/login', ['page' => '/pages/login.php']],
    ['POST', '/login', ['controller' => \App\Controllers\AuthController::class, 'action' => 'login']],
    ['GET', '/logout', ['controller' => \App\Controllers\AuthController::class, 'action' => 'logout']],
    ['GET', '/register', ['controller' => \App\Controllers\AuthController::class, 'action' => 'showRegister']],
    ['POST', '/register', ['controller' => \App\Controllers\AuthController::class, 'action' => 'register']],
    ['GET', '/forgot', ['controller' => \App\Controllers\AuthController::class, 'action' => 'showForgot']],
    ['POST', '/forgot', ['controller' => \App\Controllers\AuthController::class, 'action' => 'forgotPassword']],
    ['GET', '/reset/{token}', ['controller' => \App\Controllers\AuthController::class, 'action' => 'showReset']],
    ['POST', '/reset', ['controller' => \App\Controllers\AuthController::class, 'action' => 'resetPassword']],
    ['GET', '/2fa', ['controller' => \App\Controllers\AuthController::class, 'action' => 'show2fa']],
    ['POST', '/2fa', ['controller' => \App\Controllers\AuthController::class, 'action' => 'verify2fa']],
    ['GET', '/profile', ['controller' => \App\Controllers\AuthController::class, 'action' => 'profile']],
    ['POST', '/profile', ['controller' => \App\Controllers\AuthController::class, 'action' => 'updateProfile']],
    ['POST', '/profile/password', ['controller' => \App\Controllers\AuthController::class, 'action' => 'changePassword']],
    ['POST', '/profile/2fa/enable', ['controller' => \App\Controllers\AuthController::class, 'action' => 'enable2fa']],
    ['POST', '/profile/2fa/disable', ['controller' => \App\Controllers\AuthController::class, 'action' => 'disable2fa']],

    ['GET', '/projects', ['page' => '/pages/projects/index.php']],
    ['GET', '/projects/create', ['page' => '/pages/projects/create.php']],
    ['POST', '/projects', ['controller' => \App\Controllers\ProjectController::class, 'action' => 'store']],
    ['GET', '/projects/{id}', ['page' => '/pages/projects/show.php']],
    ['GET', '/projects/{id}/edit', ['page' => '/pages/projects/edit.php']],
    ['POST', '/projects/{id}', ['controller' => \App\Controllers\ProjectController::class, 'action' => 'update']],
    ['POST', '/projects/{id}/delete', ['controller' => \App\Controllers\ProjectController::class, 'action' => 'destroy']],
    ['POST', '/projects/{id}/upload', ['controller' => \App\Controllers\ProjectController::class, 'action' => 'upload']],

    ['GET', '/plans', ['page' => '/pages/plans/index.php']],
    ['GET', '/projects/{pid}/plans/create', ['page' => '/pages/plans/create.php']],
    ['POST', '/projects/{pid}/plans', ['controller' => \App\Controllers\PlanController::class, 'action' => 'store']],
    ['GET', '/plans/{id}/editor', ['page' => '/pages/plans/editor.php']],
    ['POST', '/plans/{id}/save', ['controller' => \App\Controllers\PlanController::class, 'action' => 'save']],
    ['GET', '/plans/{id}/export', ['page' => '/pages/plans/export.php']],
    ['POST', '/plans/{id}/export', ['controller' => \App\Controllers\PlanController::class, 'action' => 'export']],
    ['GET', '/plans/{id}/edit', ['page' => '/pages/plans/edit.php']],
    ['POST', '/plans/{id}', ['controller' => \App\Controllers\PlanController::class, 'action' => 'update']],
    ['POST', '/plans/{id}/delete', ['controller' => \App\Controllers\PlanController::class, 'action' => 'destroy']],

    ['GET', '/documents', ['controller' => \App\Controllers\DocumentController::class, 'action' => 'index']],
    ['GET', '/projects/{pid}/documents/create', ['controller' => \App\Controllers\DocumentController::class, 'action' => 'create']],
    ['POST', '/projects/{pid}/documents', ['controller' => \App\Controllers\DocumentController::class, 'action' => 'store']],
    ['GET', '/documents/{id}', ['controller' => \App\Controllers\DocumentController::class, 'action' => 'show']],
    ['GET', '/documents/{id}/edit', ['controller' => \App\Controllers\DocumentController::class, 'action' => 'edit']],
    ['POST', '/documents/{id}', ['controller' => \App\Controllers\DocumentController::class, 'action' => 'update']],
    ['POST', '/documents/{id}/delete', ['controller' => \App\Controllers\DocumentController::class, 'action' => 'destroy']],
    ['GET', '/documents/{id}/pdf', ['controller' => \App\Controllers\DocumentController::class, 'action' => 'exportPdf']],
    ['GET', '/documents/{id}/print', ['controller' => \App\Controllers\DocumentController::class, 'action' => 'printView']],

    ['GET', '/materials', ['page' => '/pages/materials/index.php']],
    ['GET', '/materials/create', ['page' => '/pages/materials/create.php']],
    ['POST', '/materials', ['controller' => \App\Controllers\MaterialController::class, 'action' => 'store']],
    ['GET', '/materials/{id}/edit', ['page' => '/pages/materials/edit.php']],
    ['POST', '/materials/{id}', ['controller' => \App\Controllers\MaterialController::class, 'action' => 'update']],
    ['POST', '/materials/{id}/delete', ['controller' => \App\Controllers\MaterialController::class, 'action' => 'destroy']],

    ['GET', '/symbols', ['page' => '/pages/symbols/index.php']],
    ['GET', '/symbols/create', ['page' => '/pages/symbols/create.php']],
    ['POST', '/symbols', ['controller' => \App\Controllers\SymbolController::class, 'action' => 'store']],
    ['POST', '/symbols/import', ['controller' => \App\Controllers\SymbolController::class, 'action' => 'import']],
    ['GET', '/symbols/{id}/edit', ['page' => '/pages/symbols/edit.php']],
    ['POST', '/symbols/{id}', ['controller' => \App\Controllers\SymbolController::class, 'action' => 'update']],
    ['POST', '/symbols/{id}/delete', ['controller' => \App\Controllers\SymbolController::class, 'action' => 'destroy']],
    ['POST', '/symbols/{id}/favourite', ['controller' => \App\Controllers\SymbolController::class, 'action' => 'toggleFavourite']],

    ['GET', '/customers', ['page' => '/pages/customers/index.php']],
    ['GET', '/customers/create', ['page' => '/pages/customers/create.php']],
    ['POST', '/customers', ['controller' => \App\Controllers\CustomerController::class, 'action' => 'store']],
    ['GET', '/customers/{id}/edit', ['page' => '/pages/customers/edit.php']],
    ['POST', '/customers/{id}', ['controller' => \App\Controllers\CustomerController::class, 'action' => 'update']],
    ['POST', '/customers/{id}/delete', ['controller' => \App\Controllers\CustomerController::class, 'action' => 'destroy']],

    ['GET', '/users', ['page' => '/pages/users/index.php']],
    ['GET', '/users/create', ['page' => '/pages/users/create.php']],
    ['POST', '/users', ['controller' => \App\Controllers\UserController::class, 'action' => 'store']],
    ['GET', '/users/{id}/edit', ['page' => '/pages/users/edit.php']],
    ['POST', '/users/{id}', ['controller' => \App\Controllers\UserController::class, 'action' => 'update']],
    ['POST', '/users/{id}/delete', ['controller' => \App\Controllers\UserController::class, 'action' => 'destroy']],

    ['GET', '/settings', ['page' => '/pages/settings/index.php']],
    ['POST', '/settings', ['controller' => \App\Controllers\SettingsController::class, 'action' => 'update']],
    ['GET', '/settings/smtp-test', ['controller' => \App\Controllers\SettingsController::class, 'action' => 'testSmtp']],

    ['GET', '/backup', ['page' => '/pages/backup/index.php']],
    ['POST', '/backup/create', ['controller' => \App\Controllers\BackupController::class, 'action' => 'create']],
    ['GET', '/backup/download/{f}', ['controller' => \App\Controllers\BackupController::class, 'action' => 'download']],
    ['POST', '/backup/restore', ['controller' => \App\Controllers\BackupController::class, 'action' => 'restore']],
    ['POST', '/backup/delete/{f}', ['controller' => \App\Controllers\BackupController::class, 'action' => 'delete']],

    ['GET', '/notifications', ['page' => '/pages/notifications/index.php']],
    ['POST', '/notifications/read/{id}', ['controller' => \App\Controllers\NotificationController::class, 'action' => 'markRead']],
    ['POST', '/notifications/read-all', ['controller' => \App\Controllers\NotificationController::class, 'action' => 'markAllRead']],

    ['POST', '/upload', ['controller' => \App\Controllers\UploadController::class, 'action' => 'store']],
    ['POST', '/upload/{id}/delete', ['controller' => \App\Controllers\UploadController::class, 'action' => 'destroy']],
    ['GET', '/dateien/{id}', ['controller' => \App\Controllers\UploadController::class, 'action' => 'serve']],
    ['GET', '/uploads/{id}', ['controller' => \App\Controllers\UploadController::class, 'action' => 'serve']],

    ['GET', '/api/plans.php', ['file' => '/api/plans.php']],
    ['POST', '/api/plans.php', ['file' => '/api/plans.php']],
    ['GET', '/api/symbols.php', ['file' => '/api/symbols.php']],
    ['POST', '/api/symbols.php', ['file' => '/api/symbols.php']],

    ['POST', '/api/v1/auth/login', ['controller' => \App\Controllers\Api\AuthApiController::class, 'action' => 'login']],
    ['POST', '/api/v1/auth/refresh', ['controller' => \App\Controllers\Api\AuthApiController::class, 'action' => 'refresh']],
    ['GET', '/api/v1/projects', ['controller' => \App\Controllers\Api\ProjectApiController::class, 'action' => 'index']],
    ['GET', '/api/v1/projects/{id}', ['controller' => \App\Controllers\Api\ProjectApiController::class, 'action' => 'show']],
    ['POST', '/api/v1/projects', ['controller' => \App\Controllers\Api\ProjectApiController::class, 'action' => 'store']],
    ['PUT', '/api/v1/projects/{id}', ['controller' => \App\Controllers\Api\ProjectApiController::class, 'action' => 'update']],
    ['DELETE', '/api/v1/projects/{id}', ['controller' => \App\Controllers\Api\ProjectApiController::class, 'action' => 'destroy']],
    ['GET', '/api/v1/plans', ['controller' => \App\Controllers\Api\PlanApiController::class, 'action' => 'index']],
    ['GET', '/api/v1/plans/{id}', ['controller' => \App\Controllers\Api\PlanApiController::class, 'action' => 'show']],
    ['GET', '/api/v1/symbols', ['file' => '/api/symbols.php']],
    ['GET', '/api/v1/symbols/{id}', ['controller' => \App\Controllers\Api\SymbolApiController::class, 'action' => 'show']],
    ['GET', '/api/v1/docs', ['controller' => \App\Controllers\Api\DocsApiController::class, 'action' => 'index']],
    ['GET', '/api/v1/openapi.json', ['controller' => \App\Controllers\Api\DocsApiController::class, 'action' => 'openapi']],
];

$effectiveMethod = $method;
if ($method === 'POST' && isset($_POST['_method'])) {
    $override = strtoupper((string) $_POST['_method']);
    if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        $effectiveMethod = $override;
    }
}
if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $override = strtoupper((string) $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
    if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        $effectiveMethod = $override;
    }
}

foreach ($routes as [$routeMethod, $pattern, $target]) {
    if ($routeMethod !== $effectiveMethod) {
        continue;
    }

    $paramNames = [];
    $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $m) use (&$paramNames): string {
        $paramNames[] = $m[1];
        return '([^/]+)';
    }, $pattern);
    $regex = '#^' . $regex . '$#';

    if (!preg_match($regex, $uri, $matches)) {
        continue;
    }

    $matches = array_slice($matches, 1);
    $params = [];
    foreach ($paramNames as $index => $name) {
        $params[$name] = $matches[$index] ?? '';
        $_GET[$name] = $params[$name];
    }

    if (isset($target['file'])) {
        require BASE_PATH . $target['file'];
        return;
    }

    if (isset($target['page'])) {
        require BASE_PATH . $target['page'];
        return;
    }

    $args = [];
    foreach ($paramNames as $name) {
        $args[] = (string) ($params[$name] ?? '');
    }

    run_controller_action((string) $target['controller'], (string) $target['action'], $args);
    return;
}

\App\Core\Response::notFound();
