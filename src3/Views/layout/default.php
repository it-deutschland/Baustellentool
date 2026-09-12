<?php

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$authUser = isset($auth) && is_array($auth) ? $auth : Auth::user();
$pageTitle = trim((string) ($title ?? 'Dashboard'));
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$unreadCount = isset($unreadNotifications) ? (int) $unreadNotifications : (int) rsa21_data_get($authUser, 'unread_notifications', 0);
$isAdmin = Auth::hasRole('admin');
$flashTypes = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
$flashes = [];
foreach ($flashTypes as $key => $class) {
    $message = Session::getFlash($key);
    if ($message !== null && $message !== '') {
        $flashes[] = ['class' => $class, 'message' => $message];
    }
}
$navGroups = [
    'Navigation' => [
        ['href' => '/dashboard', 'icon' => 'speedometer2', 'label' => 'Dashboard'],
        ['href' => '/projects', 'icon' => 'kanban', 'label' => 'Projekte'],
        ['href' => '/plans', 'icon' => 'pencil-square', 'label' => 'Pläne'],
        ['href' => '/documents', 'icon' => 'file-earmark-text', 'label' => 'Dokumente'],
        ['href' => '/materials', 'icon' => 'boxes', 'label' => 'Material'],
        ['href' => '/symbols', 'icon' => 'signpost-2', 'label' => 'Symbole'],
        ['href' => '/customers', 'icon' => 'people', 'label' => 'Kunden'],
    ],
    'Administration' => [
        ['href' => '/users', 'icon' => 'person-badge', 'label' => 'Benutzer'],
        ['href' => '/settings', 'icon' => 'gear', 'label' => 'Einstellungen'],
        ['href' => '/backup', 'icon' => 'database-check', 'label' => 'Backup'],
    ],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($pageTitle) ?> · Sonka Bau & Sonnenimmobilien - Multi Administration</title>
    <script>
        (() => {
            const match = document.cookie.match(/(?:^|; )rsa21_theme=([^;]+)/);
            const theme = match ? decodeURIComponent(match[1]) : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<div class="container-fluid">
    <div class="row min-vh-100 flex-nowrap">
        <aside class="col-12 col-lg-3 col-xxl-2 px-0">
            <div class="offcanvas-lg offcanvas-start border-end bg-body" tabindex="-1" id="appSidebar">
                <div class="offcanvas-header border-bottom">
                    <h1 class="offcanvas-title h4 mb-0">Sonka Bau & Sonnenimmobilien - Multi Administration</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Schließen"></button>
                </div>
                <div class="offcanvas-body d-flex flex-column p-3 gap-4">
                    <?php foreach ($navGroups as $group => $items): ?>
                        <?php if ($group === 'Administration' && !$isAdmin) { continue; } ?>
                        <div>
                            <div class="text-uppercase small text-body-secondary fw-semibold mb-2"><?= View::e($group) ?></div>
                            <nav class="nav nav-pills flex-column gap-1">
                                <?php foreach ($items as $item): ?>
                                    <?php $active = $currentPath === $item['href'] || str_starts_with($currentPath, rtrim($item['href'], '/') . '/'); ?>
                                    <a class="nav-link d-flex align-items-center gap-2 <?= $active ? 'active' : 'text-body' ?>" href="<?= View::e($item['href']) ?>">
                                        <i class="bi bi-<?= View::e($item['icon']) ?>"></i>
                                        <span><?= View::e($item['label']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
        <div class="col px-0 d-flex flex-column min-vh-100">
            <nav class="navbar navbar-expand-lg bg-body border-bottom sticky-top">
                <div class="container-fluid">
                    <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar"><i class="bi bi-list"></i></button>
                    <div>
                        <div class="fw-semibold">Sonka Bau & Sonnenimmobilien - Multi Administration</div>
                        <div class="small text-body-secondary"><?= View::e($pageTitle) ?></div>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button class="btn btn-outline-secondary" type="button" id="themeToggle" aria-label="Farbschema umschalten"><i class="bi bi-moon-stars"></i></button>
                        <a class="btn btn-outline-secondary position-relative" href="/notifications" aria-label="Benachrichtigungen">
                            <i class="bi bi-bell"></i>
                            <?php if ($unreadCount > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= View::e((string) $unreadCount) ?></span><?php endif; ?>
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                                <span><?= View::e((string) rsa21_data_get($authUser, 'name', 'Benutzer')) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/profile"><i class="bi bi-person me-2"></i>Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/logout"><i class="bi bi-box-arrow-right me-2"></i>Abmelden</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            <main class="flex-grow-1">
                <div class="container-fluid py-4 px-3 px-lg-4">
                    <?php foreach ($flashes as $flash): ?>
                        <div class="alert alert-<?= View::e($flash['class']) ?> alert-dismissible fade show" role="alert">
                            <?= View::e((string) $flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
                        </div>
                    <?php endforeach; ?>
                    <?= $content ?>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<script>
    (() => {
        const root = document.documentElement;
        const button = document.getElementById('themeToggle');
        const updateIcon = () => {
            const icon = button?.querySelector('i');
            if (icon) {
                icon.className = root.getAttribute('data-bs-theme') === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
            }
        };
        updateIcon();
        button?.addEventListener('click', () => {
            const next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-bs-theme', next);
            document.cookie = `rsa21_theme=${encodeURIComponent(next)}; path=/; max-age=31536000; SameSite=Lax`;
            updateIcon();
        });
    })();
</script>
</body>
</html>
