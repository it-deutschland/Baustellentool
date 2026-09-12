<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$symbol = $symbol ?? [];
$action = $action ?? '/symbols';
$submitLabel = $submitLabel ?? 'Speichern';
$fileType = (string) rsa21_data_get($symbol, 'file_type', 'svg');
?>
<form method="post" action="<?= View::e((string) $action) ?>" enctype="multipart/form-data" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-md-6"><label for="category" class="form-label">Kategorie</label><input type="text" class="form-control" id="category" name="category" value="<?= View::e((string) rsa21_data_get($symbol, 'category', '')) ?>" required></div>
    <div class="col-md-6"><label for="subcategory" class="form-label">Unterkategorie</label><input type="text" class="form-control" id="subcategory" name="subcategory" value="<?= View::e((string) rsa21_data_get($symbol, 'subcategory', '')) ?>"></div>
    <div class="col-md-8"><label for="name" class="form-label">Name</label><input type="text" class="form-control" id="name" name="name" value="<?= View::e((string) rsa21_data_get($symbol, 'name', '')) ?>" required></div>
    <div class="col-md-4"><label for="sign_number" class="form-label">Zeichennummer</label><input type="text" class="form-control" id="sign_number" name="sign_number" value="<?= View::e((string) rsa21_data_get($symbol, 'sign_number', '')) ?>"></div>
    <div class="col-md-6"><label for="symbol_file" class="form-label">Datei hochladen</label><input type="file" class="form-control" id="symbol_file" name="symbol_file" accept=".svg,.png,.jpg,.jpeg,image/svg+xml,image/png,image/jpeg"></div>
    <div class="col-md-3"><label for="file_type" class="form-label">Dateityp</label><select class="form-select" id="file_type" name="file_type"><option value="svg" <?= $fileType === 'svg' ? 'selected' : '' ?>>SVG</option><option value="png" <?= $fileType === 'png' ? 'selected' : '' ?>>PNG</option><option value="jpg" <?= $fileType === 'jpg' ? 'selected' : '' ?>>JPG</option></select></div>
    <div class="col-md-3"><label for="file_path" class="form-label">Dateipfad</label><input type="text" class="form-control" id="file_path" name="file_path" value="<?= View::e((string) rsa21_data_get($symbol, 'file_path', '')) ?>"></div>
    <div class="col-md-6"><label for="description" class="form-label">Beschreibung</label><input type="text" class="form-control" id="description" name="description" value="<?= View::e((string) rsa21_data_get($symbol, 'description', '')) ?>"></div>
    <div class="col-md-3"><label for="width_mm" class="form-label">Breite (mm)</label><input type="number" class="form-control" id="width_mm" name="width_mm" value="<?= View::e((string) rsa21_data_get($symbol, 'width_mm', '')) ?>"></div>
    <div class="col-md-3"><label for="height_mm" class="form-label">Höhe (mm)</label><input type="number" class="form-control" id="height_mm" name="height_mm" value="<?= View::e((string) rsa21_data_get($symbol, 'height_mm', '')) ?>"></div>
    <div class="col-md-6"><label for="tags" class="form-label">Tags</label><input type="text" class="form-control" id="tags" name="tags" value="<?= View::e((string) rsa21_data_get($symbol, 'tags', '')) ?>" placeholder="Baustelle, Sperrung, Umleitung"></div>
    <div class="col-md-3"><label for="license" class="form-label">Lizenz</label><input type="text" class="form-control" id="license" name="license" value="<?= View::e((string) rsa21_data_get($symbol, 'license', '')) ?>"></div>
    <div class="col-md-3"><label for="source" class="form-label">Quelle</label><input type="text" class="form-control" id="source" name="source" value="<?= View::e((string) rsa21_data_get($symbol, 'source', '')) ?>"></div>
    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" value="1" id="is_favourite" name="is_favourite" <?= rsa21_bool(rsa21_data_get($symbol, 'is_favourite', false)) ? 'checked' : '' ?>><label class="form-check-label" for="is_favourite">Als Favorit markieren</label></div></div>
    <div class="col-12 d-flex justify-content-end gap-2"><a href="/symbols" class="btn btn-outline-secondary">Abbrechen</a><button type="submit" class="btn btn-primary"><?= View::e((string) $submitLabel) ?></button></div>
</form>
