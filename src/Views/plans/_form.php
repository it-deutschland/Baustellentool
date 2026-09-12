<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$plan = $plan ?? [];
$project = $project ?? [];
$action = $action ?? '/plans';
$submitLabel = $submitLabel ?? 'Speichern';
$status = (string) rsa21_data_get($plan, 'status', 'draft');
$scale = (string) rsa21_data_get($plan, 'scale', '1:500');
?>
<form method="post" action="<?= View::e((string) $action) ?>" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-12"><label for="project_name" class="form-label">Projekt</label><input type="text" class="form-control" id="project_name" value="<?= View::e((string) rsa21_data_get($project, 'title', '')) ?>" disabled></div>
    <div class="col-md-8"><label for="title" class="form-label">Planname</label><input type="text" class="form-control" id="title" name="title" value="<?= View::e((string) rsa21_data_get($plan, 'title', '')) ?>" required></div>
    <div class="col-md-4"><label for="scale" class="form-label">Maßstab</label><select class="form-select" id="scale" name="scale"><?php foreach (['1:100', '1:200', '1:500', '1:1000', '1:2000'] as $option): ?><option value="<?= View::e($option) ?>" <?= $scale === $option ? 'selected' : '' ?>><?= View::e($option) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><label for="description" class="form-label">Beschreibung</label><textarea class="form-control" id="description" name="description" rows="4"><?= View::e((string) rsa21_data_get($plan, 'description', '')) ?></textarea></div>
    <div class="col-md-6"><label for="status" class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Entwurf</option><option value="review" <?= $status === 'review' ? 'selected' : '' ?>>In Prüfung</option><option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Freigegeben</option><option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archiviert</option></select></div>
    <div class="col-md-6 d-flex align-items-end justify-content-end gap-2"><a href="/plans" class="btn btn-outline-secondary">Abbrechen</a><button type="submit" class="btn btn-primary"><?= View::e((string) $submitLabel) ?></button></div>
</form>
