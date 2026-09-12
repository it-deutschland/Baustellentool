<?php

use App\Core\CSRF;
use App\Core\View;
?>
<div class="text-center mb-4">
    <h1 class="h3 mb-2">Konto erstellen</h1>
    <p class="text-body-secondary mb-0">Erstellen Sie ein neues Benutzerkonto für Sonka Bau & Sonnenimmobilien - Multi Administration.</p>
</div>
<form method="post" action="/register" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-12"><label for="name" class="form-label">Name</label><input type="text" class="form-control" id="name" name="name" value="<?= View::e((string) ($name ?? '')) ?>" required></div>
    <div class="col-12"><label for="email" class="form-label">E-Mail</label><input type="email" class="form-control" id="email" name="email" value="<?= View::e((string) ($email ?? '')) ?>" required></div>
    <div class="col-md-6"><label for="password" class="form-label">Passwort</label><input type="password" class="form-control" id="password" name="password" required></div>
    <div class="col-md-6"><label for="password_confirmation" class="form-label">Passwort bestätigen</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required></div>
    <div class="col-12 d-grid"><button type="submit" class="btn btn-primary btn-lg">Registrieren</button></div>
</form>
<div class="text-center mt-4"><a href="/login" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Zurück zur Anmeldung</a></div>
