<?php

use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$document = $document ?? [];
$project = $project ?? [];
$contentHtml = (string) ($contentHtml ?? rsa21_data_get($document, 'content', ''));
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4"><div><div class="text-body-secondary small"><?= View::e((string) rsa21_data_get($project, 'title', 'Projekt')) ?></div><h1 class="h2 mb-1"><?= View::e((string) rsa21_data_get($document, 'title', 'Dokument')) ?></h1></div><div class="d-flex flex-wrap gap-2"><span class="badge bg-secondary align-self-center"><?= View::e((string) rsa21_data_get($document, 'status', 'draft')) ?></span><a href="/documents/<?= View::e((string) rsa21_data_get($document, 'id', '')) ?>/edit" class="btn btn-outline-secondary">Bearbeiten</a><a href="/documents/<?= View::e((string) rsa21_data_get($document, 'id', '')) ?>/pdf" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-2"></i>PDF exportieren</a><a href="/documents/<?= View::e((string) rsa21_data_get($document, 'id', '')) ?>/print" class="btn btn-outline-primary"><i class="bi bi-printer me-2"></i>Drucken</a></div></div>
<div class="card shadow-sm"><div class="card-body"><?= $contentHtml ?></div></div>
