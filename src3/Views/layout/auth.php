<?php

use App\Core\Session;
use App\Core\View;

$pageTitle = trim((string) ($title ?? 'Anmeldung'));
$flashTypes = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
$flashes = [];
foreach ($flashTypes as $key => $class) {
    $message = Session::getFlash($key);
    if ($message !== null && $message !== '') {
        $flashes[] = ['class' => $class, 'message' => $message];
    }
}
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
<body class="auth-gradient min-vh-100 d-flex flex-column">
<nav class="navbar bg-transparent py-3">
    <div class="container">
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="/"><i class="bi bi-cone-striped"></i><span>Sonka Bau & Sonnenimmobilien - Multi Administration</span></a>
        <button class="btn btn-outline-secondary" type="button" id="themeToggle" aria-label="Farbschema umschalten"><i class="bi bi-moon-stars"></i></button>
    </div>
</nav>
<main class="container flex-grow-1 d-flex align-items-center justify-content-center py-4">
    <div class="col-12 col-md-10 col-lg-7 col-xl-5">
        <div class="card border-0 shadow-lg glass-card">
            <div class="card-body p-4 p-lg-5">
                <?php foreach ($flashes as $flash): ?>
                    <div class="alert alert-<?= View::e($flash['class']) ?> alert-dismissible fade show" role="alert">
                        <?= View::e((string) $flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
                    </div>
                <?php endforeach; ?>
                <?= $content ?>
            </div>
        </div>
    </div>
</main>
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
