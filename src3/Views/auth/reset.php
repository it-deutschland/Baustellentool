<?php

use App\Core\CSRF;
use App\Core\View;
?>
<div class="text-center mb-4">
    <h1 class="h3 mb-2">Passwort zurücksetzen</h1>
    <p class="text-body-secondary mb-0">Vergeben Sie ein neues Passwort für Ihr Benutzerkonto.</p>
</div>
<form method="post" action="/reset" class="row g-3">
    <?= CSRF::field() ?>
    <input type="hidden" name="token" value="<?= View::e((string) ($token ?? '')) ?>">
    <div class="col-12"><label for="password" class="form-label">Neues Passwort</label><input type="password" class="form-control" id="password" name="password" required></div>
    <div class="col-12"><label for="password_confirmation" class="form-label">Passwort bestätigen</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required></div>
    <div class="col-12 d-grid"><button type="submit" class="btn btn-primary btn-lg">Passwort speichern</button></div>
</form>
