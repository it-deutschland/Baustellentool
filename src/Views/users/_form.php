<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$user = $user ?? [];
$roles = rsa21_data_list($roles ?? []);
$action = $action ?? '/users';
$submitLabel = $submitLabel ?? 'Speichern';
?>
<form method="post" action="<?= View::e((string) $action) ?>" enctype="multipart/form-data" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-md-6"><label for="name" class="form-label">Name</label><input type="text" class="form-control" id="name" name="name" value="<?= View::e((string) rsa21_data_get($user, 'name', '')) ?>" required></div>
    <div class="col-md-6"><label for="email" class="form-label">E-Mail</label><input type="email" class="form-control" id="email" name="email" value="<?= View::e((string) rsa21_data_get($user, 'email', '')) ?>" required></div>
    <div class="col-md-6"><label for="phone" class="form-label">Telefon</label><input type="text" class="form-control" id="phone" name="phone" value="<?= View::e((string) rsa21_data_get($user, 'phone', '')) ?>"></div>
    <div class="col-md-6"><label for="role_id" class="form-label">Rolle</label><select class="form-select" id="role_id" name="role_id" required><option value="">Bitte wählen</option><?php foreach ($roles as $role): ?><?php $roleId = (string) rsa21_data_get($role, 'id', ''); ?><option value="<?= View::e($roleId) ?>" <?= (string) rsa21_data_get($user, 'role_id', '') === $roleId ? 'selected' : '' ?>><?= View::e((string) rsa21_data_get($role, 'display_name', rsa21_data_get($role, 'name', 'Rolle'))) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label for="avatar" class="form-label">Avatar</label><input type="file" class="form-control" id="avatar" name="avatar" accept="image/*"></div>
    <div class="col-md-6"><label for="is_active" class="form-label">Status</label><select class="form-select" id="is_active" name="is_active"><option value="1" <?= rsa21_bool(rsa21_data_get($user, 'is_active', true)) ? 'selected' : '' ?>>Aktiv</option><option value="0" <?= !rsa21_bool(rsa21_data_get($user, 'is_active', true)) ? 'selected' : '' ?>>Inaktiv</option></select></div>
    <div class="col-md-6"><label for="password" class="form-label">Passwort</label><input type="password" class="form-control" id="password" name="password" <?= empty(rsa21_data_get($user, 'id', null)) ? 'required' : '' ?>></div>
    <div class="col-md-6"><label for="password_confirmation" class="form-label">Passwort bestätigen</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" <?= empty(rsa21_data_get($user, 'id', null)) ? 'required' : '' ?>></div>
    <div class="col-12 d-flex justify-content-end gap-2"><a href="/users" class="btn btn-outline-secondary">Abbrechen</a><button type="submit" class="btn btn-primary"><?= View::e((string) $submitLabel) ?></button></div>
</form>
