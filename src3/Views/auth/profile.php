<?php

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$user = isset($user) ? $user : Auth::user();
$qrUrl = $twoFactorQrUrl ?? $qrCodeUrl ?? rsa21_data_get($user, 'two_factor_qr_url', '');
$twoFactorEnabled = rsa21_bool(rsa21_data_get($user, 'two_factor_enabled', false));
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div><h1 class="h2 mb-1">Mein Profil</h1><p class="text-body-secondary mb-0">Verwalten Sie Kontaktdaten, Kennwort und Zwei-Faktor-Authentifizierung.</p></div>
</div>
<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profil-tab" type="button">Profil</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#password-tab" type="button">Passwort ändern</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#twofa-tab" type="button">Zwei-Faktor</button></li>
</ul>
<div class="tab-content border border-top-0 rounded-bottom p-4 bg-body">
    <div class="tab-pane fade show active" id="profil-tab">
        <form method="post" action="/profile" enctype="multipart/form-data" class="row g-3">
            <?= CSRF::field() ?>
            <div class="col-lg-4">
                <div class="card h-100"><div class="card-body text-center">
                    <?php if ((string) rsa21_data_get($user, 'avatar', '') !== ''): ?><img src="<?= View::e((string) rsa21_data_get($user, 'avatar')) ?>" alt="Avatar" class="img-thumbnail rounded-circle mb-3"><?php else: ?><div class="display-3 text-body-secondary mb-3"><i class="bi bi-person-circle"></i></div><?php endif; ?>
                    <label for="avatar" class="form-label">Avatar aktualisieren</label><input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                </div></div>
            </div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-md-6"><label for="name" class="form-label">Name</label><input type="text" class="form-control" id="name" name="name" value="<?= View::e((string) rsa21_data_get($user, 'name', '')) ?>" required></div>
                <div class="col-md-6"><label for="email" class="form-label">E-Mail</label><input type="email" class="form-control" id="email" name="email" value="<?= View::e((string) rsa21_data_get($user, 'email', '')) ?>" required></div>
                <div class="col-md-6"><label for="phone" class="form-label">Telefon</label><input type="text" class="form-control" id="phone" name="phone" value="<?= View::e((string) rsa21_data_get($user, 'phone', '')) ?>"></div>
                <div class="col-md-6"><label for="role" class="form-label">Rolle</label><input type="text" class="form-control" id="role" value="<?= View::e((string) (rsa21_data_get($user, 'role_name', rsa21_data_get($user, 'role', 'Benutzer')))) ?>" disabled></div>
                <div class="col-12 d-flex justify-content-end"><button type="submit" class="btn btn-primary">Profil speichern</button></div>
            </div></div>
        </form>
    </div>
    <div class="tab-pane fade" id="password-tab">
        <form method="post" action="/profile/password" class="row g-3">
            <?= CSRF::field() ?>
            <div class="col-md-4"><label for="current_password" class="form-label">Aktuelles Passwort</label><input type="password" class="form-control" id="current_password" name="current_password" required></div>
            <div class="col-md-4"><label for="new_password" class="form-label">Neues Passwort</label><input type="password" class="form-control" id="new_password" name="password" required></div>
            <div class="col-md-4"><label for="new_password_confirmation" class="form-label">Bestätigung</label><input type="password" class="form-control" id="new_password_confirmation" name="password_confirmation" required></div>
            <div class="col-12 d-flex justify-content-end"><button type="submit" class="btn btn-primary">Passwort ändern</button></div>
        </form>
    </div>
    <div class="tab-pane fade" id="twofa-tab">
        <div class="row g-4 align-items-start">
            <div class="col-lg-7"><div class="card h-100"><div class="card-body">
                <h2 class="h5">Status</h2>
                <p class="mb-3 text-body-secondary">Aktueller Status: <span class="badge <?= $twoFactorEnabled ? 'bg-success' : 'bg-secondary' ?>"><?= $twoFactorEnabled ? 'Aktiviert' : 'Deaktiviert' ?></span></p>
                <?php if ($twoFactorEnabled): ?><form method="post" action="/profile/2fa/disable"><?= CSRF::field() ?><button type="submit" class="btn btn-outline-danger">2FA deaktivieren</button></form><?php else: ?><form method="post" action="/profile/2fa/enable"><?= CSRF::field() ?><button type="submit" class="btn btn-primary">2FA aktivieren</button></form><?php endif; ?>
            </div></div></div>
            <div class="col-lg-5"><div class="card h-100"><div class="card-body">
                <h2 class="h5">QR-Code / Einrichtungslink</h2>
                <?php if ((string) $qrUrl !== ''): ?><div class="border rounded p-3 bg-body-tertiary mb-3 text-break"><a href="<?= View::e((string) $qrUrl) ?>" target="_blank" rel="noopener noreferrer"><?= View::e((string) $qrUrl) ?></a></div><img src="<?= View::e((string) $qrUrl) ?>" alt="QR-Code für 2FA" class="img-fluid rounded border"><?php else: ?><p class="text-body-secondary mb-0">Sobald 2FA aktiviert ist, erscheint hier der Einrichtungslink.</p><?php endif; ?>
            </div></div></div>
        </div>
    </div>
</div>
