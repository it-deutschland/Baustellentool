<?php

use App\Core\CSRF;
?>
<div class="text-center mb-4">
    <h1 class="h3 mb-2">Zwei-Faktor-Bestätigung</h1>
    <p class="text-body-secondary mb-0">Bitte geben Sie den sechsstelligen Bestätigungscode aus Ihrer Authenticator-App ein.</p>
</div>
<form method="post" action="/2fa" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-12"><label for="code" class="form-label">Sicherheitscode</label><input type="text" class="form-control form-control-lg text-center" id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required></div>
    <div class="col-12 d-grid"><button type="submit" class="btn btn-primary btn-lg">Code prüfen</button></div>
</form>
