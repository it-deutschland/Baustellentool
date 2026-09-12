<?php

use App\Core\CSRF;
use App\Core\View;

require_once VIEWS_PATH . '/_helpers.php';

$plan = $plan ?? [];
$project = $project ?? [];
$canvasData = rsa21_data_get($plan, 'canvas_data', '{}');
if (!is_string($canvasData) || trim($canvasData) === '') {
    $canvasData = '{}';
}
$planId    = (string) rsa21_data_get($plan, 'id', '');
$planScale = (string) rsa21_data_get($plan, 'scale', '1:500');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= View::e(CSRF::token()) ?>">
    <title><?= View::e((string) rsa21_data_get($plan, 'title', 'Plan-Editor')) ?> · Sonka Bau & Sonnenimmobilien - Multi Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { margin: 0; overflow: hidden; }
        .editor-shell { display: grid; grid-template-columns: minmax(0, 3fr) minmax(320px, 1fr); grid-template-rows: auto auto auto 1fr auto; height: 100vh; }
        .editor-col { min-width: 0; display: flex; flex-direction: column; }
        .editor-canvas-wrap { position: relative; flex: 1; overflow: hidden; background: var(--bs-tertiary-bg); }
        .editor-canvas-stage { position: absolute; inset: 0; padding: 1rem; }
        #canvas-area { width: 100%; height: 100%; }
        #plan-canvas { width: 100%; height: 100%; background-image: linear-gradient(to right, rgba(127,127,127,.15) 1px, transparent 1px), linear-gradient(to bottom, rgba(127,127,127,.15) 1px, transparent 1px); background-size: 24px 24px; border-radius: .75rem; box-shadow: inset 0 0 0 1px rgba(127,127,127,.18); }
        .editor-sidebar { border-left: 1px solid var(--bs-border-color); display: flex; flex-direction: column; min-height: 0; }
        .sidebar-scroll { overflow: auto; min-height: 0; }
        .symbol-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: .75rem; }
        .symbol-item { cursor: grab; }
        .toolbar-scroll { overflow-x: auto; }
        .toolbar-scroll .btn-toolbar { flex-wrap: nowrap; }
        .status-bar { font-size: .875rem; }
        #props-panel { display: none; }
        @media (max-width: 991.98px) {
            .editor-shell { grid-template-columns: 1fr; grid-template-rows: auto auto auto minmax(320px, 1fr) auto auto; }
            .editor-sidebar { border-left: 0; border-top: 1px solid var(--bs-border-color); }
        }
    </style>
</head>
<body>
<div class="editor-shell bg-body">


    <header class="editor-col border-bottom bg-body px-3 px-xl-4 py-3">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
            <div>
                <div class="text-body-secondary small"><?= View::e((string) rsa21_data_get($project, 'title', 'Projekt')) ?></div>
                <h1 class="h4 mb-0"><?= View::e((string) rsa21_data_get($plan, 'title', 'Plan-Editor')) ?></h1>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="btn-group">
                    <button class="btn btn-outline-secondary" type="button" id="btn-undo" title="Rückgängig (Strg+Z)"><i class="bi bi-arrow-counterclockwise"></i></button>
                    <button class="btn btn-outline-secondary" type="button" id="btn-redo" title="Wiederholen (Strg+Y)"><i class="bi bi-arrow-clockwise"></i></button>
                </div>
                <select class="form-select" id="plan-scale" style="width:auto;">
                    <?php foreach (['1:100', '1:200', '1:500', '1:1000', '1:2000'] as $s): ?>
                        <option value="<?= View::e($s) ?>" <?= $planScale === $s ? 'selected' : '' ?>><?= View::e($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="badge text-bg-secondary" id="autosave-badge">Auto-Save bereit</span>
                <button class="btn btn-success" type="button" id="btn-save" title="Speichern (Strg+S)"><i class="bi bi-floppy me-1"></i>Speichern</button>
                <button class="btn btn-primary" type="button" id="btn-export" title="Export"><i class="bi bi-download me-1"></i>Export</button>
                <a href="/plans" class="btn btn-outline-secondary" title="Schließen"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </header>


    <div class="editor-col border-bottom bg-body px-3 px-xl-4 py-2 toolbar-scroll">
        <div class="btn-toolbar gap-2">
            <div class="btn-group">
                <button class="btn btn-outline-secondary tool-btn active" type="button" data-tool="select" title="Auswahl"><i class="bi bi-cursor"></i> Auswahl</button>
                <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="pan" title="Bewegen"><i class="bi bi-hand-index"></i> Bewegen</button>
                <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="pencil" title="Freihand"><i class="bi bi-pencil"></i> Zeichnen</button>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="rect" title="Rechteck"><i class="bi bi-square"></i></button>
                <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="circle" title="Kreis"><i class="bi bi-circle"></i></button>
                <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="line" title="Linie"><i class="bi bi-slash-lg"></i></button>
                <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="arrow" title="Pfeil"><i class="bi bi-arrow-up-right"></i></button>
                <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="text" title="Text"><i class="bi bi-type"></i></button>
            </div>
            <button class="btn btn-outline-secondary" type="button" id="btn-add-image" title="Bild einfügen"><i class="bi bi-image"></i> Bild</button>
            <div class="btn-group ms-auto">
                <button class="btn btn-outline-secondary" type="button" id="btn-copy" title="Kopieren (Strg+C)"><i class="bi bi-copy"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-paste" title="Einfügen (Strg+V)"><i class="bi bi-clipboard"></i></button>
                <button class="btn btn-outline-danger" type="button" id="btn-delete" title="Löschen (Entf)"><i class="bi bi-trash"></i></button>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary" type="button" id="btn-group" title="Gruppieren (Strg+G)"><i class="bi bi-bounding-box"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-ungroup" title="Gruppierung aufheben"><i class="bi bi-bounding-box-circles"></i></button>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary" type="button" id="btn-mirror-h" title="Horizontal spiegeln"><i class="bi bi-symmetry-horizontal"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-mirror-v" title="Vertikal spiegeln"><i class="bi bi-symmetry-vertical"></i></button>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary" type="button" id="btn-align-left" title="Links ausrichten"><i class="bi bi-align-start"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-align-centerH" title="Horizontal zentrieren"><i class="bi bi-align-center"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-align-right" title="Rechts ausrichten"><i class="bi bi-align-end"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-align-top" title="Oben ausrichten"><i class="bi bi-align-top"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-align-centerV" title="Vertikal zentrieren"><i class="bi bi-align-middle"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-align-bottom" title="Unten ausrichten"><i class="bi bi-align-bottom"></i></button>
            </div>
        </div>
    </div>


    <div class="editor-col border-bottom bg-body px-3 px-xl-4 py-2 toolbar-scroll">
        <div class="btn-toolbar gap-2">
            <div class="btn-group">
                <button class="btn btn-outline-secondary" type="button" id="btn-zoom-out" title="Verkleinern"><i class="bi bi-zoom-out"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-zoom-reset" title="Zoom zurücksetzen"><i class="bi bi-arrows-fullscreen"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-zoom-in" title="Vergrößern"><i class="bi bi-zoom-in"></i></button>
                <span class="btn btn-outline-secondary disabled" id="zoom-level" style="min-width:4rem;">100 %</span>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary" type="button" id="btn-grid" title="Raster ein/aus"><i class="bi bi-grid-3x3-gap"></i> Raster</button>
                <button class="btn btn-outline-secondary active" type="button" id="btn-snap" title="Am Raster einrasten"><i class="bi bi-magnet"></i> Snap</button>
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#grid-modal" title="Rastereinstellungen"><i class="bi bi-layout-wtf"></i> Rastereinst.</button>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary" type="button" id="btn-bring-front" title="In den Vordergrund"><i class="bi bi-layer-forward"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-bring-forward" title="Eine Ebene nach vorne"><i class="bi bi-front"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-send-backward" title="Eine Ebene nach hinten"><i class="bi bi-back"></i></button>
                <button class="btn btn-outline-secondary" type="button" id="btn-send-back" title="In den Hintergrund"><i class="bi bi-layer-backward"></i></button>
            </div>
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#layers-modal" title="Ebenen"><i class="bi bi-layers"></i> Ebenen</button>
        </div>
    </div>


    <section class="editor-col">
        <div class="editor-canvas-wrap" id="canvas-area">
            <div class="editor-canvas-stage" id="canvas-wrapper">
                <canvas id="plan-canvas"></canvas>
            </div>
        </div>
    </section>


    <aside class="editor-sidebar bg-body">
        <div class="sidebar-scroll">
        
            <div class="p-3 border-bottom">
                <h2 class="h5 mb-3">Symbolbibliothek</h2>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" id="symbol-search" placeholder="Symbole suchen">
                </div>
            </div>
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Katalog</h3>
                    <span class="badge text-bg-secondary" id="symbol-count">0</span>
                </div>
                <div class="symbol-grid" id="symbol-grid">
                    <div class="card text-center p-3 text-body-secondary"><div class="small">Symbole werden geladen…</div></div>
                </div>
            </div>

        
            <div class="p-3" id="props-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Eigenschaften</h3>
                    <span class="badge text-bg-light" id="selection-state">Keine Auswahl</span>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small" for="prop-x">X</label>
                        <input type="number" class="form-control form-control-sm" id="prop-x">
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="prop-y">Y</label>
                        <input type="number" class="form-control form-control-sm" id="prop-y">
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="prop-w">Breite</label>
                        <input type="number" class="form-control form-control-sm" id="prop-w">
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="prop-h">Höhe</label>
                        <input type="number" class="form-control form-control-sm" id="prop-h">
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="prop-rot">Rotation</label>
                        <input type="number" class="form-control form-control-sm" id="prop-rot">
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="prop-opacity">Deckkraft %</label>
                        <input type="number" class="form-control form-control-sm" id="prop-opacity" min="0" max="100">
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="prop-fill">Füllung</label>
                        <input type="color" class="form-control form-control-color" id="prop-fill" value="#0d6efd">
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="prop-stroke">Kontur</label>
                        <input type="color" class="form-control form-control-color" id="prop-stroke" value="#333333">
                    </div>
                </div>
            </div>
        </div>
    </aside>


    <footer class="editor-col border-top bg-body px-3 px-xl-4 py-2 status-bar">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div class="d-flex flex-wrap gap-3">
                <span><i class="bi bi-crosshair me-1"></i><span id="status-x">X: 0</span> · <span id="status-y">Y: 0</span></span>
                <span><i class="bi bi-zoom-in me-1"></i><span id="zoom-level-footer">100 %</span></span>
                <span><i class="bi bi-rulers me-1"></i><span id="status-scale">Maßstab <?= View::e($planScale) ?></span></span>
            </div>
            <div class="text-body-secondary">Plan #<?= View::e($planId) ?></div>
        </div>
    </footer>
</div>

<input type="file" id="img-upload-input" class="d-none" accept="image/*">


<div class="modal fade" id="export-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5">Export</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label" for="export-format">Format</label>
                    <select class="form-select" id="export-format">
                        <option value="svg">SVG</option>
                        <option value="png">PNG</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="exportResolution">Qualität</label>
                    <select class="form-select" id="exportResolution">
                        <option value="150">150 DPI</option>
                        <option value="300" selected>300 DPI</option>
                        <option value="600">600 DPI</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="exportCredits">Credits / Quellenangabe</label>
                    <input type="text" class="form-control" id="exportCredits" placeholder="z. B. © 2025 Muster GmbH">
                </div>
                <div class="col-12 d-none" id="exportCreditsNote">
                    <div class="alert alert-info py-2 mb-0 small"><i class="bi bi-info-circle me-1"></i>Die Credits werden als Hinweis im exportierten Plan angezeigt.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-outline-secondary" id="btn-export-svg">SVG</button>
                <button type="button" class="btn btn-outline-secondary" id="btn-export-png">PNG</button>
                <button type="button" class="btn btn-primary" id="btn-export-pdf">PDF</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="layers-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5">Ebenenverwaltung</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="modal-btn-bring-front"><i class="bi bi-layer-forward"></i> Ganz vorne</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="modal-btn-bring-forward"><i class="bi bi-front"></i> Vorne</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="modal-btn-send-backward"><i class="bi bi-back"></i> Hinten</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="modal-btn-send-back"><i class="bi bi-layer-backward"></i> Ganz hinten</button>
                </div>
                <div class="list-group" id="layers-list">
                    <div class="list-group-item text-body-secondary">Ebenen werden durch den Editor geladen.</div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="grid-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5">Rastereinstellungen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label" for="grid-size-input">Rastergröße (px)</label>
                    <input type="number" class="form-control" id="grid-size-input" value="20" min="5" max="200">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="grid-snap-input" checked>
                        <label class="form-check-label" for="grid-snap-input">Am Raster ausrichten</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-primary" id="btn-apply-grid" data-bs-dismiss="modal">Übernehmen</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
<script>
window.rsa21PlanEditor = {
    plan: <?= json_encode([
        'id'         => rsa21_data_get($plan, 'id', null),
        'title'      => rsa21_data_get($plan, 'title', ''),
        'scale'      => $planScale,
        'canvasData' => json_decode($canvasData, true) ?? new stdClass(),
        'saveUrl'    => '/plans/' . $planId . '/save',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    project: <?= json_encode([
        'id'    => rsa21_data_get($project, 'id', null),
        'title' => rsa21_data_get($project, 'title', ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    symbolsApi: '/api/v1/symbols',
    csrfToken: <?= json_encode(CSRF::token(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
};

// Autosave status badge
window.RSA21 = window.RSA21 || {};
window.RSA21.setAutoSaveStatus = function (status) {
    var badge = document.getElementById('autosave-badge');
    if (!badge) return;
    var map = {
        saving: ['text-bg-warning', 'Speichert…'],
        saved:  ['text-bg-success', 'Gespeichert'],
        error:  ['text-bg-danger',  'Fehler beim Speichern'],
    };
    var info = map[status] || ['text-bg-secondary', 'Auto-Save bereit'];
    badge.className = 'badge ' + info[0];
    badge.textContent = info[1];
};
</script>
<script src="/assets/js/plan-editor.js"></script>
<script>
// Initialise editor after scripts are loaded
document.addEventListener('DOMContentLoaded', function () {
    var cfg = window.rsa21PlanEditor;
    if (typeof initEditor === 'function' && cfg) {
        initEditor(cfg.plan.id, cfg.project.id, cfg.plan.canvasData);
    }

    // Wire up export modal button
    var btnExport = document.getElementById('btn-export');
    if (btnExport) {
        btnExport.addEventListener('click', function () {
            var modal = new bootstrap.Modal(document.getElementById('export-modal'));
            modal.show();
        });
    }

    // Grid modal – apply button
    var btnApplyGrid = document.getElementById('btn-apply-grid');
    if (btnApplyGrid) {
        btnApplyGrid.addEventListener('click', function () {
            var sizeInput = document.getElementById('grid-size-input');
            var snapInput = document.getElementById('grid-snap-input');
            if (typeof Editor !== 'undefined') {
                if (sizeInput) Editor.gridSize = Math.max(5, parseInt(sizeInput.value, 10) || 20);
                if (snapInput) Editor.snapEnabled = snapInput.checked;
                if (Editor.gridEnabled) {
                    if (typeof clearGrid === 'function') clearGrid();
                    if (typeof drawGrid === 'function') drawGrid();
                }
            }
        });
    }

    // Layer buttons in modal mirror the toolbar buttons
    var layerMap = {
        'modal-btn-bring-front':   'btn-bring-front',
        'modal-btn-bring-forward': 'btn-bring-forward',
        'modal-btn-send-backward': 'btn-send-backward',
        'modal-btn-send-back':     'btn-send-back',
    };
    Object.keys(layerMap).forEach(function (modalId) {
        var modalBtn = document.getElementById(modalId);
        var toolbarBtn = document.getElementById(layerMap[modalId]);
        if (modalBtn && toolbarBtn) {
            modalBtn.addEventListener('click', function () { toolbarBtn.click(); });
        }
    });
});
</script>
</body>
</html>
