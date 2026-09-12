<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$customers = rsa21_data_list($customers ?? []);
$search = (string) ($search ?? '');
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4"><div><h1 class="h2 mb-1">Kunden</h1><p class="text-body-secondary mb-0">Verwalten Sie Firmen, Kontakte und Kommunikationsdaten Ihrer Auftraggeber.</p></div><a href="/customers/create" class="btn btn-primary"><i class="bi bi-building-add me-2"></i>Kunde anlegen</a></div>
<div class="card shadow-sm mb-4"><div class="card-body"><form method="get" action="/customers" class="row g-3 align-items-end"><?= CSRF::field() ?><div class="col-md-10"><label class="form-label" for="customer-search">Suche</label><input type="search" class="form-control" id="customer-search" name="search" value="<?= View::e($search) ?>" placeholder="Firma, Kontakt oder Ort"></div><div class="col-md-2 d-grid"><button type="submit" class="btn btn-outline-primary">Filtern</button></div></form></div></div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Firma</th><th>Kontakt</th><th>E-Mail</th><th>Telefon</th><th>Ort</th><th class="text-end">Aktionen</th></tr></thead><tbody><?php if ($customers === []): ?><tr><td colspan="6" class="text-center text-body-secondary py-5">Keine Kunden vorhanden.</td></tr><?php endif; ?><?php foreach ($customers as $customer): ?><tr><td class="fw-semibold"><?= View::e((string) rsa21_data_get($customer, 'company', 'Kunde')) ?></td><td><?= View::e((string) rsa21_data_get($customer, 'contact_name', '—')) ?></td><td><?= View::e((string) rsa21_data_get($customer, 'email', '—')) ?></td><td><?= View::e((string) rsa21_data_get($customer, 'phone', '—')) ?></td><td><?= View::e(trim((string) rsa21_data_get($customer, 'zip', '') . ' ' . (string) rsa21_data_get($customer, 'city', ''))) ?></td><td class="text-end"><a href="/customers/<?= View::e((string) rsa21_data_get($customer, 'id', '')) ?>/edit" class="btn btn-sm btn-outline-secondary">Bearbeiten</a></td></tr><?php endforeach; ?></tbody></table></div></div>
