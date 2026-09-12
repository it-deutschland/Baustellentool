<?php

use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$users = rsa21_data_list($users ?? []);
$roleClasses = ['Administrator' => 'danger', 'Bearbeiter' => 'primary', 'Prüfer' => 'warning', 'Gast' => 'secondary'];
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4"><div><h1 class="h2 mb-1">Benutzer</h1><p class="text-body-secondary mb-0">Benutzerkonten, Rollen und Zugriffsstatus verwalten.</p></div><a href="/users/create" class="btn btn-primary"><i class="bi bi-person-plus me-2"></i>Benutzer anlegen</a></div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Name</th><th>E-Mail</th><th>Telefon</th><th>Rolle</th><th>Status</th><th>Letzte Anmeldung</th><th class="text-end">Aktionen</th></tr></thead><tbody><?php if ($users === []): ?><tr><td colspan="7" class="text-center text-body-secondary py-5">Keine Benutzer vorhanden.</td></tr><?php endif; ?><?php foreach ($users as $user): ?><?php $roleName = (string) rsa21_data_get($user, 'role_name', rsa21_data_get($user, 'role', 'Gast')); ?><tr><td class="fw-semibold"><?= View::e((string) rsa21_data_get($user, 'name', 'Benutzer')) ?></td><td><?= View::e((string) rsa21_data_get($user, 'email', '')) ?></td><td><?= View::e((string) rsa21_data_get($user, 'phone', '—')) ?></td><td><span class="badge text-bg-<?= View::e($roleClasses[$roleName] ?? 'secondary') ?>"><?= View::e($roleName) ?></span></td><td><span class="badge <?= rsa21_bool(rsa21_data_get($user, 'is_active', true)) ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= rsa21_bool(rsa21_data_get($user, 'is_active', true)) ? 'Aktiv' : 'Inaktiv' ?></span></td><td><?= rsa21_date(rsa21_data_get($user, 'last_login_at', '')) ?></td><td class="text-end"><a href="/users/<?= View::e((string) rsa21_data_get($user, 'id', '')) ?>/edit" class="btn btn-sm btn-outline-secondary">Bearbeiten</a></td></tr><?php endforeach; ?></tbody></table></div></div>
