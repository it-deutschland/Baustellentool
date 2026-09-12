<?php

use App\Core\View;

$debugMessage = '';
if (defined('APP_DEBUG') && APP_DEBUG) {
    $debugMessage = (string) ($message ?? ($exception?->getMessage() ?? ''));
}
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>500 · Sonka Bau & Sonnenimmobilien - Multi Administration</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><link href="/assets/css/app.css" rel="stylesheet"></head><body class="bg-body-tertiary d-flex align-items-center min-vh-100"><main class="container"><div class="row justify-content-center"><div class="col-lg-7"><div class="card shadow-sm"><div class="card-body p-5 text-center"><div class="display-4 text-danger mb-3"><i class="bi bi-exclamation-octagon"></i></div><h1 class="h2 mb-3">Interner Serverfehler</h1><p class="text-body-secondary mb-4">Beim Verarbeiten Ihrer Anfrage ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.</p><?php if ($debugMessage !== ''): ?><div class="alert alert-danger text-start mb-4"><?= View::e($debugMessage) ?></div><?php endif; ?><a href="/" class="btn btn-primary"><i class="bi bi-house-door me-2"></i>Zur Startseite</a></div></div></div></div></main></body></html>
