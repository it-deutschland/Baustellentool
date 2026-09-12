<?php

use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$stats = rsa21_data_list($stats ?? []);
$recentProjects = rsa21_data_list($recentProjects ?? []);
$recentLogs = rsa21_data_list($recentLogs ?? []);
$lowStockMaterials = rsa21_data_list($lowStockMaterials ?? []);
$recentDocuments = rsa21_data_list($recentDocuments ?? []);
$statCards = [
    ['label' => 'Aktive Projekte', 'value' => $stats['active'] ?? 0, 'icon' => 'kanban', 'class' => 'primary'],
    ['label' => 'Entwürfe', 'value' => $stats['draft'] ?? 0, 'icon' => 'file-earmark', 'class' => 'secondary'],
    ['label' => 'Abgeschlossen', 'value' => $stats['completed'] ?? 0, 'icon' => 'check2-circle', 'class' => 'success'],
    ['label' => 'Gesamt', 'value' => $stats['total'] ?? array_sum(array_map('intval', $stats)), 'icon' => 'bar-chart', 'class' => 'info'],
];
$statusClasses = ['draft' => 'bg-secondary', 'active' => 'bg-primary', 'review' => 'bg-warning text-dark', 'completed' => 'bg-success', 'archived' => 'bg-dark'];
$statusLabels = ['draft' => 'Entwurf', 'active' => 'Aktiv', 'review' => 'In Prüfung', 'completed' => 'Abgeschlossen', 'archived' => 'Archiviert'];
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div><h1 class="h2 mb-1">Dashboard</h1><p class="text-body-secondary mb-0">Willkommen zurück. Hier finden Sie die wichtigsten Kennzahlen und aktuellen Aktivitäten.</p></div>
    <a href="/notifications" class="btn btn-outline-primary"><i class="bi bi-bell me-2"></i><?= View::e((string) ($unreadNotifications ?? 0)) ?> ungelesene Benachrichtigungen</a>
</div>
<div class="row g-3 mb-4">
    <?php foreach ($statCards as $card): ?>
        <div class="col-12 col-md-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="text-body-secondary small text-uppercase fw-semibold"><?= View::e($card['label']) ?></div><div class="display-6 fw-semibold"><?= View::e((string) $card['value']) ?></div></div><div class="fs-1 text-<?= View::e($card['class']) ?>"><i class="bi bi-<?= View::e($card['icon']) ?>"></i></div></div></div></div>
    <?php endforeach; ?>
</div>
<div class="row g-4">
    <div class="col-12 col-xl-8"><div class="card shadow-sm h-100"><div class="card-header d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Aktuelle Projekte</h2><a href="/projects" class="btn btn-sm btn-outline-primary">Alle Projekte</a></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Projekt</th><th>Status</th><th>Priorität</th><th>Aktualisiert</th></tr></thead><tbody><?php if ($recentProjects === []): ?><tr><td colspan="4" class="text-center text-body-secondary py-4">Keine Projekte vorhanden.</td></tr><?php endif; ?><?php foreach ($recentProjects as $project): ?><?php $status = (string) rsa21_data_get($project, 'status', 'draft'); ?><tr><td><div class="fw-semibold"><a href="/projects/<?= View::e((string) rsa21_data_get($project, 'id', '')) ?>" class="text-decoration-none"><?= View::e((string) rsa21_data_get($project, 'title', 'Ohne Titel')) ?></a></div><div class="small text-body-secondary"><?= View::e((string) rsa21_data_get($project, 'project_number', '')) ?></div></td><td><span class="badge <?= View::e($statusClasses[$status] ?? 'bg-secondary') ?>"><?= View::e($statusLabels[$status] ?? ucfirst($status)) ?></span></td><td><?= View::e((string) rsa21_data_get($project, 'priority', 'normal')) ?></td><td><?= rsa21_date(rsa21_data_get($project, 'updated_at', ''), 'd.m.Y H:i') ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm mb-4"><div class="card-header"><h2 class="h5 mb-0">Letzte Aktivitäten</h2></div><div class="list-group list-group-flush"><?php if ($recentLogs === []): ?><div class="list-group-item text-body-secondary">Keine Aktivitäten vorhanden.</div><?php endif; ?><?php foreach ($recentLogs as $log): ?><div class="list-group-item"><div class="fw-semibold"><?= View::e((string) rsa21_data_get($log, 'action', 'Aktivität')) ?></div><div class="small text-body-secondary"><?= View::e((string) rsa21_data_get($log, 'module', 'System')) ?> · <?= rsa21_date(rsa21_data_get($log, 'created_at', '')) ?></div></div><?php endforeach; ?></div></div>
        <div class="card shadow-sm"><div class="card-header"><h2 class="h5 mb-0">Letzte Dokumente</h2></div><div class="list-group list-group-flush"><?php if ($recentDocuments === []): ?><div class="list-group-item text-body-secondary">Keine Dokumente vorhanden.</div><?php endif; ?><?php foreach ($recentDocuments as $document): ?><a class="list-group-item list-group-item-action" href="/documents/<?= View::e((string) rsa21_data_get($document, 'id', '')) ?>"><div class="fw-semibold"><?= View::e((string) rsa21_data_get($document, 'title', 'Dokument')) ?></div><div class="small text-body-secondary"><?= rsa21_date(rsa21_data_get($document, 'updated_at', '')) ?></div></a><?php endforeach; ?></div></div>
    </div>
</div>
<?php if ($lowStockMaterials !== []): ?>
    <div class="card shadow-sm mt-4 border-warning"><div class="card-header bg-warning-subtle"><h2 class="h5 mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Niedriger Materialbestand</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Material</th><th>Bestand</th><th>Mindestbestand</th><th>Einheit</th></tr></thead><tbody><?php foreach ($lowStockMaterials as $material): ?><tr><td><?= View::e((string) rsa21_data_get($material, 'name', 'Material')) ?></td><td><?= View::e((string) rsa21_data_get($material, 'stock', '0')) ?></td><td><?= View::e((string) rsa21_data_get($material, 'min_stock', '0')) ?></td><td><?= View::e((string) rsa21_data_get($material, 'unit', 'Stk')) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php endif; ?>
