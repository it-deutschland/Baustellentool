<?php

use App\Core\CSRF;
use App\Core\View;
?>
<div class="text-center mb-4">
    <h1 class="h3 mb-2">Willkommen zurück</h1>
    <p class="text-body-secondary mb-0">Melden Sie sich an, um Projekte, Pläne und Dokumente zu verwalten.</p>
</div>
<form method="post" action="/login" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-12"><label for="email" class="form-label">E-Mail</label><input type="email" class="form-control" id="email" name="email" value="<?= View::e((string) ($email ?? '')) ?>" autocomplete="email" required></div>
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center"><label for="password" class="form-label mb-0">Passwort</label><a href="/forgot" class="small text-decoration-none">Passwort vergessen?</a></div>
        <input type="password" class="form-control mt-2" id="password" name="password" autocomplete="current-password" required>
    </div>
    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" value="1" id="remember" name="remember"><label class="form-check-label" for="remember">Angemeldet bleiben</label></div></div>
    <div class="col-12 d-grid"><button type="submit" class="btn btn-primary btn-lg">Anmelden</button></div>
</form>
<div class="text-center mt-4"><span class="text-body-secondary">Noch kein Konto?</span><a href="/register" class="text-decoration-none ms-1">Jetzt registrieren</a></div>
