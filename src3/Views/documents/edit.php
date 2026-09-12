<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$document = $document ?? [];
$project = $project ?? [];
$typeOptions = is_array($typeOptions ?? null) ? $typeOptions : [];
$formDefinitions = is_array($formDefinitions ?? null) ? $formDefinitions : [];
$fieldValues = is_array($fieldValues ?? null) ? $fieldValues : [];
$legacyContent = (string) ($legacyContent ?? '');
$currentType = (string) rsa21_data_get($document, 'type', 'other');
$status = (string) rsa21_data_get($document, 'status', 'draft');
$legalDisclaimer = $legalDisclaimer ?? ($settings['legal_disclaimer'] ?? $settings['legalDisclaimer'] ?? '');
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4"><div><div class="text-body-secondary small"><?= View::e((string) rsa21_data_get($project, 'title', 'Projekt')) ?></div><h1 class="h2 mb-1">Dokument bearbeiten</h1></div><a href="/documents/<?= View::e((string) rsa21_data_get($document, 'id', '')) ?>/pdf" class="btn btn-outline-primary">PDF exportieren</a></div>
<div class="alert alert-warning"><i class="bi bi-shield-exclamation me-2"></i><?= View::e((string) $legalDisclaimer) ?></div>
<div class="card shadow-sm"><div class="card-body"><form method="post" action="/documents/<?= View::e((string) rsa21_data_get($document, 'id', '')) ?>" class="row g-3" id="documentEditorForm"><?= CSRF::field() ?><div class="col-md-8"><label for="doc-title" class="form-label">Titel</label><input class="form-control" id="doc-title" name="title" value="<?= View::e((string) rsa21_data_get($document, 'title', 'Dokument')) ?>" required></div><div class="col-md-4"><label for="doc-type" class="form-label">Dokumenttyp</label><select class="form-select" id="doc-type" name="type"><?php foreach ($typeOptions as $typeKey => $typeLabel): ?><option value="<?= View::e((string) $typeKey) ?>" <?= $currentType === (string) $typeKey ? 'selected' : '' ?>><?= View::e((string) $typeLabel) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label for="doc-status" class="form-label">Status</label><select class="form-select" id="doc-status" name="status"><option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Entwurf</option><option value="review" <?= $status === 'review' ? 'selected' : '' ?>>In Prüfung</option><option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Freigegeben</option><option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archiviert</option></select></div><?php foreach ($formDefinitions as $typeKey => $definitions): ?><div class="col-12 type-form-block" data-doc-type="<?= View::e((string) $typeKey) ?>"><div class="border rounded p-3 bg-body-tertiary"><h2 class="h6 mb-3">Formularfelder: <?= View::e((string) ($typeOptions[$typeKey] ?? $typeKey)) ?></h2><div class="row g-3"><?php foreach (rsa21_data_list($definitions) as $definition): ?><?php $fieldKey = (string) rsa21_data_get($definition, 'key', ''); if ($fieldKey === '') { continue; } ?><?php $fieldType = (string) rsa21_data_get($definition, 'type', 'text'); ?><?php $fieldLabel = (string) rsa21_data_get($definition, 'label', $fieldKey); ?><?php $fieldValue = $currentType === (string) $typeKey ? (string) ($fieldValues[$fieldKey] ?? '') : ''; ?><div class="col-md-6"><label class="form-label" for="field-<?= View::e((string) $typeKey . '-' . $fieldKey) ?>"><?= View::e($fieldLabel) ?></label><?php if ($fieldType === 'textarea'): ?><textarea class="form-control" id="field-<?= View::e((string) $typeKey . '-' . $fieldKey) ?>" name="fields[<?= View::e((string) $typeKey) ?>][<?= View::e($fieldKey) ?>]" rows="4"><?= View::e($fieldValue) ?></textarea><?php elseif ($fieldType === 'date'): ?><input type="date" class="form-control" id="field-<?= View::e((string) $typeKey . '-' . $fieldKey) ?>" name="fields[<?= View::e((string) $typeKey) ?>][<?= View::e($fieldKey) ?>]" value="<?= View::e($fieldValue) ?>"><?php elseif ($fieldType === 'checkbox'): ?><div class="form-check pt-2"><input class="form-check-input" type="checkbox" id="field-<?= View::e((string) $typeKey . '-' . $fieldKey) ?>" name="fields[<?= View::e((string) $typeKey) ?>][<?= View::e($fieldKey) ?>]" value="1" <?= rsa21_bool($fieldValue) ? 'checked' : '' ?>><label class="form-check-label" for="field-<?= View::e((string) $typeKey . '-' . $fieldKey) ?>">Ja</label></div><?php elseif ($fieldType === 'select'): ?><select class="form-select" id="field-<?= View::e((string) $typeKey . '-' . $fieldKey) ?>" name="fields[<?= View::e((string) $typeKey) ?>][<?= View::e($fieldKey) ?>]"><?php foreach ((array) rsa21_data_get($definition, 'options', []) as $optionValue => $optionLabel): ?><option value="<?= View::e((string) $optionValue) ?>" <?= $fieldValue === (string) $optionValue ? 'selected' : '' ?>><?= View::e((string) $optionLabel) ?></option><?php endforeach; ?></select><?php else: ?><input class="form-control" id="field-<?= View::e((string) $typeKey . '-' . $fieldKey) ?>" name="fields[<?= View::e((string) $typeKey) ?>][<?= View::e($fieldKey) ?>]" value="<?= View::e($fieldValue) ?>"><?php endif; ?></div><?php endforeach; ?></div></div></div><?php endforeach; ?><?php if ($legacyContent !== ''): ?><div class="col-12"><label for="legacy-content" class="form-label">Legacy-Inhalt (nur Übergang)</label><textarea class="form-control" id="legacy-content" name="content" rows="6"><?= View::e($legacyContent) ?></textarea></div><?php endif; ?><div class="col-12 d-flex justify-content-end gap-2"><a href="/documents/<?= View::e((string) rsa21_data_get($document, 'id', '')) ?>" class="btn btn-outline-secondary">Abbrechen</a><button type="submit" class="btn btn-primary">Speichern</button></div></form></div></div>
<script>
(() => {
    const typeSelect = document.getElementById('doc-type');
    const blocks = Array.from(document.querySelectorAll('.type-form-block'));
    if (!typeSelect || blocks.length === 0) return;

    const toggle = () => {
        const selected = typeSelect.value;
        blocks.forEach((block) => {
            const isActive = block.getAttribute('data-doc-type') === selected;
            block.style.display = isActive ? '' : 'none';
            block.querySelectorAll('input, textarea, select').forEach((field) => {
                if (field.name.startsWith('fields[')) {
                    field.disabled = !isActive;
                }
            });
        });
    };

    typeSelect.addEventListener('change', toggle);
    toggle();
})();
</script>
