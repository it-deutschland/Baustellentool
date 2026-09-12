<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$customer = $customer ?? [];
$action = $action ?? '/customers';
$submitLabel = $submitLabel ?? 'Speichern';
?>
<form method="post" action="<?= View::e((string) $action) ?>" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-md-8"><label class="form-label" for="company">Firma</label><input class="form-control" id="company" name="company" value="<?= View::e((string) rsa21_data_get($customer, 'company', '')) ?>" required></div>
    <div class="col-md-4"><label class="form-label" for="contact_name">Kontaktperson</label><input class="form-control" id="contact_name" name="contact_name" value="<?= View::e((string) rsa21_data_get($customer, 'contact_name', '')) ?>"></div>
    <div class="col-md-6"><label class="form-label" for="email">E-Mail</label><input class="form-control" id="email" name="email" type="email" value="<?= View::e((string) rsa21_data_get($customer, 'email', '')) ?>"></div>
    <div class="col-md-6"><label class="form-label" for="phone">Telefon</label><input class="form-control" id="phone" name="phone" value="<?= View::e((string) rsa21_data_get($customer, 'phone', '')) ?>"></div>
    <div class="col-md-4"><label class="form-label" for="zip">PLZ</label><input class="form-control" id="zip" name="zip" value="<?= View::e((string) rsa21_data_get($customer, 'zip', '')) ?>"></div>
    <div class="col-md-4"><label class="form-label" for="city">Ort</label><input class="form-control" id="city" name="city" value="<?= View::e((string) rsa21_data_get($customer, 'city', '')) ?>"></div>
    <div class="col-md-4"><label class="form-label" for="country">Land</label><input class="form-control" id="country" name="country" value="<?= View::e((string) rsa21_data_get($customer, 'country', 'Deutschland')) ?>"></div>
    <div class="col-12"><label class="form-label" for="address">Adresse</label><textarea class="form-control" id="address" name="address" rows="3"><?= View::e((string) rsa21_data_get($customer, 'address', '')) ?></textarea></div>
    <div class="col-12"><label class="form-label" for="notes">Notizen</label><textarea class="form-control" id="notes" name="notes" rows="4"><?= View::e((string) rsa21_data_get($customer, 'notes', '')) ?></textarea></div>
    <div class="col-12 d-flex justify-content-end gap-2"><a href="/customers" class="btn btn-outline-secondary">Abbrechen</a><button type="submit" class="btn btn-primary"><?= View::e((string) $submitLabel) ?></button></div>
</form>
