<?php

use App\Core\CSRF;
use App\Core\View;
?>
<div class="text-center mb-4">
    <h1 class="h3 mb-2">Passwort vergessen</h1>
    <p class="text-body-secondary mb-0">Wir senden Ihnen einen Link zum Zurücksetzen des Passworts an Ihre E-Mail-Adresse.</p>
</div>
<form method="post" action="/forgot" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-12"><label for="email" class="form-label">E-Mail</label><input type="email" class="form-control" id="email" name="email" value="<?= View::e((string) ($email ?? '')) ?>" required></div>
    <div class="col-12 d-grid"><button type="submit" class="btn btn-primary btn-lg">Link anfordern</button></div>
</form>
<div class="text-center mt-4"><a href="/login" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Zurück zur Anmeldung</a></div>
