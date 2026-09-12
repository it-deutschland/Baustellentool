<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$project = $project ?? [];
$customers = rsa21_data_list($customers ?? []);
$users = rsa21_data_list($users ?? []);
$action = $action ?? '/projects';
$submitLabel = $submitLabel ?? 'Speichern';
$status = (string) rsa21_data_get($project, 'status', 'draft');
$priority = (string) rsa21_data_get($project, 'priority', 'normal');
?>
<form method="post" action="<?= View::e((string) $action) ?>" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-12 col-lg-6"><label for="title" class="form-label">Titel</label><input type="text" class="form-control" id="title" name="title" value="<?= View::e((string) rsa21_data_get($project, 'title', '')) ?>" required></div>
    <div class="col-12 col-lg-6"><label for="customer_id" class="form-label">Kunde</label><select class="form-select" id="customer_id" name="customer_id"><option value="">Bitte wählen</option><?php foreach ($customers as $customer): ?><?php $customerId = (string) rsa21_data_get($customer, 'id', ''); ?><option value="<?= View::e($customerId) ?>" <?= (string) rsa21_data_get($project, 'customer_id', '') === $customerId ? 'selected' : '' ?>><?= View::e((string) rsa21_data_get($customer, 'company', rsa21_data_get($customer, 'name', 'Kunde'))) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><label for="description" class="form-label">Beschreibung</label><textarea class="form-control" id="description" name="description" rows="4"><?= View::e((string) rsa21_data_get($project, 'description', '')) ?></textarea></div>
    <div class="col-md-6"><label for="location" class="form-label">Ort</label><input type="text" class="form-control" id="location" name="location" value="<?= View::e((string) rsa21_data_get($project, 'location', '')) ?>"></div>
    <div class="col-md-6"><label for="address" class="form-label">Adresse</label><input type="text" class="form-control" id="address" name="address" value="<?= View::e((string) rsa21_data_get($project, 'address', '')) ?>"></div>
    <div class="col-md-6 col-lg-3"><label for="gps_lat" class="form-label">GPS Breitengrad</label><input type="text" class="form-control" id="gps_lat" name="gps_lat" value="<?= View::e((string) rsa21_data_get($project, 'gps_lat', '')) ?>"></div>
    <div class="col-md-6 col-lg-3"><label for="gps_lng" class="form-label">GPS Längengrad</label><input type="text" class="form-control" id="gps_lng" name="gps_lng" value="<?= View::e((string) rsa21_data_get($project, 'gps_lng', '')) ?>"></div>
    <div class="col-md-6 col-lg-3"><label for="priority" class="form-label">Priorität</label><select class="form-select" id="priority" name="priority"><option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Niedrig</option><option value="normal" <?= $priority === 'normal' ? 'selected' : '' ?>>Normal</option><option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>Hoch</option><option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>Dringend</option></select></div>
    <div class="col-md-6 col-lg-3"><label for="status" class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Entwurf</option><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktiv</option><option value="review" <?= $status === 'review' ? 'selected' : '' ?>>In Prüfung</option><option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Abgeschlossen</option><option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archiviert</option></select></div>
    <div class="col-md-4"><label for="contact_name" class="form-label">Kontaktperson</label><input type="text" class="form-control" id="contact_name" name="contact_name" value="<?= View::e((string) rsa21_data_get($project, 'contact_name', '')) ?>"></div>
    <div class="col-md-4"><label for="contact_phone" class="form-label">Kontakt Telefon</label><input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?= View::e((string) rsa21_data_get($project, 'contact_phone', '')) ?>"></div>
    <div class="col-md-4"><label for="contact_email" class="form-label">Kontakt E-Mail</label><input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= View::e((string) rsa21_data_get($project, 'contact_email', '')) ?>"></div>
    <div class="col-md-6"><label for="start_date" class="form-label">Startdatum</label><input type="date" class="form-control" id="start_date" name="start_date" value="<?= View::e((string) rsa21_data_get($project, 'start_date', '')) ?>"></div>
    <div class="col-md-6"><label for="end_date" class="form-label">Enddatum</label><input type="date" class="form-control" id="end_date" name="end_date" value="<?= View::e((string) rsa21_data_get($project, 'end_date', '')) ?>"></div>
    <div class="col-12"><label for="assigned_to" class="form-label">Zugewiesen an</label><select class="form-select" id="assigned_to" name="assigned_to"><option value="">Nicht zugewiesen</option><?php foreach ($users as $user): ?><?php $userId = (string) rsa21_data_get($user, 'id', ''); ?><option value="<?= View::e($userId) ?>" <?= (string) rsa21_data_get($project, 'assigned_to', '') === $userId ? 'selected' : '' ?>><?= View::e((string) rsa21_data_get($user, 'name', 'Benutzer')) ?></option><?php endforeach; ?></select></div>
    <div class="col-12 d-flex justify-content-end gap-2"><a href="/projects" class="btn btn-outline-secondary">Abbrechen</a><button type="submit" class="btn btn-primary"><?= View::e((string) $submitLabel) ?></button></div>
</form>
