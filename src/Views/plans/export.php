<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$plan = $plan ?? [];
?>
<div class="row justify-content-center"><div class="col-xl-7"><div class="card shadow-sm"><div class="card-header"><h1 class="h4 mb-0">Plan exportieren</h1></div><div class="card-body"><form method="post" action="/plans/<?= View::e((string) rsa21_data_get($plan, 'id', '')) ?>/export" class="row g-3"><?= CSRF::field() ?><div class="col-md-6"><label for="format" class="form-label">Format</label><select class="form-select" id="format" name="format"><option value="pdf">PDF</option><option value="png">PNG</option><option value="svg">SVG</option></select></div><div class="col-md-6"><label for="dpi" class="form-label">Auflösung</label><select class="form-select" id="dpi" name="dpi"><option value="150">150 DPI</option><option value="300" selected>300 DPI</option><option value="600">600 DPI</option></select></div><div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" value="1" id="include_legend" name="include_legend" checked><label class="form-check-label" for="include_legend">Legende einschließen</label></div></div><div class="col-12 d-flex justify-content-end gap-2"><a href="/plans/<?= View::e((string) rsa21_data_get($plan, 'id', '')) ?>/editor" class="btn btn-outline-secondary">Zurück</a><button type="submit" class="btn btn-primary">Export starten</button></div></form></div></div></div></div>
