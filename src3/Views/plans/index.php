<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$plans = rsa21_data_list($plans ?? []);
$statusLabels = ['draft' => 'Entwurf', 'review' => 'In Prüfung', 'approved' => 'Freigegeben', 'archived' => 'Archiviert'];
$statusClasses = ['draft' => 'bg-secondary', 'review' => 'bg-warning text-dark', 'approved' => 'bg-success', 'archived' => 'bg-dark'];
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4"><div><h1 class="h2 mb-1">Pläne</h1><p class="text-body-secondary mb-0">Alle Pläne mit Projektbezug, Status und Bearbeitungsstand im Überblick.</p></div><form method="get" action="/plans" class="row g-2 align-items-end"><?= CSRF::field() ?><div class="col-auto"><label for="plan-search" class="form-label">Suche</label><input type="search" class="form-control" id="plan-search" name="search" value="<?= View::e((string) ($search ?? '')) ?>" placeholder="Titel oder Projekt"></div><div class="col-auto"><button type="submit" class="btn btn-outline-primary">Filtern</button></div></form></div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Plan</th><th>Projekt</th><th>Status</th><th>Maßstab</th><th>Daten</th><th class="text-end">Aktionen</th></tr></thead><tbody><?php if ($plans === []): ?><tr><td colspan="6" class="text-center text-body-secondary py-5">Keine Pläne vorhanden.</td></tr><?php endif; ?><?php foreach ($plans as $plan): ?><?php $status = (string) rsa21_data_get($plan, 'status', 'draft'); ?><tr><td><div class="fw-semibold"><?= View::e((string) rsa21_data_get($plan, 'title', 'Plan')) ?></div><div class="small text-body-secondary">Version <?= View::e((string) rsa21_data_get($plan, 'version', '1')) ?></div></td><td><?= View::e((string) rsa21_data_get($plan, 'project_title', rsa21_data_get($plan, 'project_name', '—'))) ?></td><td><span class="badge <?= View::e($statusClasses[$status] ?? 'bg-secondary') ?>"><?= View::e($statusLabels[$status] ?? ucfirst($status)) ?></span></td><td><?= View::e((string) rsa21_data_get($plan, 'scale', '1:500')) ?></td><td><?= rsa21_date(rsa21_data_get($plan, 'updated_at', '')) ?></td><td class="text-end"><div class="btn-group"><a href="/plans/<?= View::e((string) rsa21_data_get($plan, 'id', '')) ?>/editor" class="btn btn-sm btn-outline-primary">Editor</a><a href="/plans/<?= View::e((string) rsa21_data_get($plan, 'id', '')) ?>/edit" class="btn btn-sm btn-outline-secondary">Bearbeiten</a></div></td></tr><?php endforeach; ?></tbody></table></div></div>
