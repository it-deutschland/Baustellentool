<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$material = $material ?? [];
$action = $action ?? '/materials';
$submitLabel = $submitLabel ?? 'Speichern';
?>
<form method="post" action="<?= View::e((string) $action) ?>" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-md-4"><label for="category" class="form-label">Kategorie</label><input type="text" class="form-control" id="category" name="category" value="<?= View::e((string) rsa21_data_get($material, 'category', '')) ?>" required></div>
    <div class="col-md-8"><label for="name" class="form-label">Name</label><input type="text" class="form-control" id="name" name="name" value="<?= View::e((string) rsa21_data_get($material, 'name', '')) ?>" required></div>
    <div class="col-md-4"><label for="article_no" class="form-label">Artikelnummer</label><input type="text" class="form-control" id="article_no" name="article_no" value="<?= View::e((string) rsa21_data_get($material, 'article_no', '')) ?>"></div>
    <div class="col-md-4"><label for="unit" class="form-label">Einheit</label><input type="text" class="form-control" id="unit" name="unit" value="<?= View::e((string) rsa21_data_get($material, 'unit', 'Stk')) ?>"></div>
    <div class="col-md-4"><label for="supplier" class="form-label">Lieferant</label><input type="text" class="form-control" id="supplier" name="supplier" value="<?= View::e((string) rsa21_data_get($material, 'supplier', '')) ?>"></div>
    <div class="col-md-4"><label for="stock" class="form-label">Bestand</label><input type="number" step="0.01" class="form-control" id="stock" name="stock" value="<?= View::e((string) rsa21_data_get($material, 'stock', '0')) ?>"></div>
    <div class="col-md-4"><label for="min_stock" class="form-label">Mindestbestand</label><input type="number" step="0.01" class="form-control" id="min_stock" name="min_stock" value="<?= View::e((string) rsa21_data_get($material, 'min_stock', '0')) ?>"></div>
    <div class="col-md-4"><label for="price" class="form-label">Preis</label><input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= View::e((string) rsa21_data_get($material, 'price', '')) ?>"></div>
    <div class="col-md-6"><label for="location" class="form-label">Lagerort</label><input type="text" class="form-control" id="location" name="location" value="<?= View::e((string) rsa21_data_get($material, 'location', '')) ?>"></div>
    <div class="col-md-6"><label for="description" class="form-label">Beschreibung</label><input type="text" class="form-control" id="description" name="description" value="<?= View::e((string) rsa21_data_get($material, 'description', '')) ?>"></div>
    <div class="col-12 d-flex justify-content-end gap-2"><a href="/materials" class="btn btn-outline-secondary">Abbrechen</a><button type="submit" class="btn btn-primary"><?= View::e((string) $submitLabel) ?></button></div>
</form>
