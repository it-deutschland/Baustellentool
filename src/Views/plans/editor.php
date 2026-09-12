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
        html, body { height: 100%; margin: 0; overflow: hidden; }

        /* ── Top header bar ───────────────────────────────────────── */
        .ed-topbar {
            position: fixed; top: 0; left: 0; right: 0; height: 50px;
            display: flex; align-items: center; gap: .5rem;
            padding: 0 .75rem;
            background: var(--bg-navbar, #fff);
            border-bottom: 1px solid var(--bs-border-color);
            z-index: 200;
        }
        .ed-topbar .ed-title { font-weight: 600; font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
        .ed-topbar .ed-subtitle { font-size: .75rem; color: var(--text-muted, #6c757d); white-space: nowrap; }

        /* ── Left toolbar ─────────────────────────────────────────── */
        .ed-toolbar {
            position: fixed; top: 50px; left: 0; bottom: 26px; width: 50px;
            background: var(--bg-navbar, #fff);
            border-right: 1px solid var(--bs-border-color);
            z-index: 190;
            display: flex; flex-direction: column; align-items: center;
            padding: .375rem 0;
            overflow-y: auto; overflow-x: hidden;
        }
        .ed-toolbar .btn {
            width: 38px; height: 38px;
            padding: 0; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 1rem;
        }
        .ed-toolbar .ed-sep {
            width: 30px; height: 1px;
            background: var(--bs-border-color);
            margin: .25rem 0; flex-shrink: 0;
        }

        /* ── Left drawer (symbols + props) ───────────────────────── */
        .ed-drawer {
            position: fixed; top: 50px; left: 50px; bottom: 26px; width: 280px;
            background: var(--bg-card, #fff);
            border-right: 1px solid var(--bs-border-color);
            z-index: 180;
            display: flex; flex-direction: column;
            transform: translateX(-280px);
            transition: transform .2s ease;
            overflow: hidden;
        }
        .ed-drawer.open { transform: translateX(0); }
        .ed-drawer-scroll { overflow-y: auto; flex: 1; min-height: 0; }
        .symbol-toolbar { display: grid; grid-template-columns: 1fr; gap: .5rem; }
        .symbol-list { display: flex; flex-direction: column; gap: .5rem; }
        .symbol-list-item {
            display: grid; grid-template-columns: 24px minmax(0, 1fr) auto auto; gap: .5rem;
            align-items: center; padding: .5rem; border: 1px solid var(--bs-border-color);
            border-radius: .5rem; background: var(--bs-body-bg);
        }
        .symbol-preview {
            width: 20px; height: 20px; object-fit: contain;
            display: block; justify-self: center;
        }
        .symbol-meta { min-width: 0; }
        .symbol-number { font-size: .75rem; font-weight: 700; line-height: 1.1; }
        .symbol-name { font-size: .75rem; color: var(--text-muted, #6c757d); line-height: 1.2; }
        .symbol-empty {
            border: 1px dashed var(--bs-border-color);
            border-radius: .5rem;
            padding: .875rem;
            color: var(--text-muted, #6c757d);
            font-size: .8rem;
            text-align: center;
        }
        .geometry-toolbar { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .geometry-toolbar .form-select { width: auto; min-width: 180px; }

        /* ── Canvas area ──────────────────────────────────────────── */
        .ed-canvas-wrap {
            position: fixed;
            top: 50px; left: 50px; right: 0; bottom: 26px;
            background: var(--bs-tertiary-bg, #f0f2f5);
            transition: left .2s ease;
        }
        .ed-canvas-wrap.drawer-open { left: 330px; }
        .ed-canvas-stage {
            position: absolute; inset: 0; padding: .5rem;
        }
        #canvas-area { width: 100%; height: 100%; }
        #plan-canvas {
            width: 100%; height: 100%;
            background-image: linear-gradient(to right, rgba(127,127,127,.15) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(127,127,127,.15) 1px, transparent 1px);
            background-size: 24px 24px;
            border-radius: .5rem;
            box-shadow: inset 0 0 0 1px rgba(127,127,127,.18);
        }

        /* ── Status bar ───────────────────────────────────────────── */
        .ed-statusbar {
            position: fixed; bottom: 0; left: 50px; right: 0; height: 26px;
            display: flex; align-items: center; padding: 0 .75rem; gap: 1rem;
            font-size: .8rem; color: var(--text-muted, #6c757d);
            background: var(--bg-navbar, #fff);
            border-top: 1px solid var(--bs-border-color);
            z-index: 200;
        }

        /* Properties panel (inside drawer) */
        #props-panel { display: none; }

        /* zoom badge in topbar */
        #zoom-level { display: none; }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════
     TOP HEADER BAR
════════════════════════════════════════════════════════ -->
<header class="ed-topbar bg-body">
    <a href="/plans" class="btn btn-outline-secondary btn-sm" title="Schließen"><i class="bi bi-arrow-left"></i></a>
    <div class="d-flex flex-column lh-sm me-2">
        <span class="ed-subtitle"><?= View::e((string) rsa21_data_get($project, 'title', 'Projekt')) ?></span>
        <span class="ed-title"><?= View::e((string) rsa21_data_get($plan, 'title', 'Plan-Editor')) ?></span>
    </div>
    <div class="btn-group btn-group-sm">
        <button class="btn btn-outline-secondary" type="button" id="btn-undo" title="Rückgängig (Strg+Z)"><i class="bi bi-arrow-counterclockwise"></i></button>
        <button class="btn btn-outline-secondary" type="button" id="btn-redo" title="Wiederholen (Strg+Y)"><i class="bi bi-arrow-clockwise"></i></button>
    </div>
    <select class="form-select form-select-sm" id="plan-scale" style="width:auto;">
        <?php foreach (['1:100', '1:200', '1:500', '1:1000', '1:2000'] as $s): ?>
            <option value="<?= View::e($s) ?>" <?= $planScale === $s ? 'selected' : '' ?>><?= View::e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="geometry-toolbar">
        <select class="form-select form-select-sm" id="geometry-category" title="Kategorie Planeditor">
            <option value="">Planeditor-Kategorie</option>
        </select>
        <select class="form-select form-select-sm" id="geometry-template" title="Geometrisches Element">
            <option value="">Element auswählen</option>
        </select>
        <button class="btn btn-sm btn-outline-primary" type="button" id="btn-add-geometry"><i class="bi bi-bezier2 me-1"></i>Einfügen</button>
    </div>
    <span class="badge text-bg-secondary ms-1" id="autosave-badge">Auto-Save bereit</span>
    <div class="ms-auto d-flex gap-2">
        <button class="btn btn-sm btn-success" type="button" id="btn-save" title="Speichern (Strg+S)"><i class="bi bi-floppy me-1"></i>Speichern</button>
        <button class="btn btn-sm btn-primary" type="button" id="btn-export" title="Export"><i class="bi bi-download me-1"></i>Export</button>
    </div>
</header>

<!-- ═══════════════════════════════════════════════════════
     LEFT TOOLBAR
════════════════════════════════════════════════════════ -->
<nav class="ed-toolbar bg-body" id="ed-toolbar">

    <!-- Symbols drawer toggle -->
    <button class="btn btn-outline-secondary mb-1" type="button" id="btn-toggle-drawer" title="Symbolbibliothek ein/aus"><i class="bi bi-layout-sidebar"></i></button>

    <div class="ed-sep"></div>

    <!-- Cursor tools -->
    <button class="btn btn-outline-secondary tool-btn active" type="button" data-tool="select" title="Auswahl"><i class="bi bi-cursor"></i></button>
    <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="pan" title="Bewegen"><i class="bi bi-hand-index"></i></button>
    <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="pencil" title="Freihand zeichnen"><i class="bi bi-pencil"></i></button>

    <div class="ed-sep"></div>

    <!-- Shape tools -->
    <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="rect" title="Rechteck"><i class="bi bi-square"></i></button>
    <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="circle" title="Kreis"><i class="bi bi-circle"></i></button>
    <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="line" title="Linie"><i class="bi bi-slash-lg"></i></button>
    <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="arrow" title="Pfeil"><i class="bi bi-arrow-up-right"></i></button>
    <button class="btn btn-outline-secondary tool-btn" type="button" data-tool="text" title="Text"><i class="bi bi-type"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-add-image" title="Bild einfügen"><i class="bi bi-image"></i></button>

    <div class="ed-sep"></div>

    <!-- Edit -->
    <button class="btn btn-outline-secondary" type="button" id="btn-copy" title="Kopieren (Strg+C)"><i class="bi bi-copy"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-paste" title="Einfügen (Strg+V)"><i class="bi bi-clipboard"></i></button>
    <button class="btn btn-outline-danger" type="button" id="btn-delete" title="Löschen (Entf)"><i class="bi bi-trash"></i></button>

    <div class="ed-sep"></div>

    <!-- Group -->
    <button class="btn btn-outline-secondary" type="button" id="btn-group" title="Gruppieren (Strg+G)"><i class="bi bi-bounding-box"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-ungroup" title="Gruppierung aufheben"><i class="bi bi-bounding-box-circles"></i></button>

    <div class="ed-sep"></div>

    <!-- Mirror -->
    <button class="btn btn-outline-secondary" type="button" id="btn-mirror-h" title="Horizontal spiegeln"><i class="bi bi-symmetry-horizontal"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-mirror-v" title="Vertikal spiegeln"><i class="bi bi-symmetry-vertical"></i></button>

    <div class="ed-sep"></div>

    <!-- Align -->
    <button class="btn btn-outline-secondary" type="button" id="btn-align-left" title="Links ausrichten"><i class="bi bi-align-start"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-align-centerH" title="Horizontal zentrieren"><i class="bi bi-align-center"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-align-right" title="Rechts ausrichten"><i class="bi bi-align-end"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-align-top" title="Oben ausrichten"><i class="bi bi-align-top"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-align-centerV" title="Vertikal zentrieren"><i class="bi bi-align-middle"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-align-bottom" title="Unten ausrichten"><i class="bi bi-align-bottom"></i></button>

    <div class="ed-sep"></div>

    <!-- Zoom -->
    <button class="btn btn-outline-secondary" type="button" id="btn-zoom-out" title="Verkleinern"><i class="bi bi-zoom-out"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-zoom-reset" title="Zoom zurücksetzen"><i class="bi bi-arrows-fullscreen"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-zoom-in" title="Vergrößern"><i class="bi bi-zoom-in"></i></button>

    <div class="ed-sep"></div>

    <!-- Grid / Snap -->
    <button class="btn btn-outline-secondary" type="button" id="btn-grid" title="Raster ein/aus"><i class="bi bi-grid-3x3-gap"></i></button>
    <button class="btn btn-outline-secondary active" type="button" id="btn-snap" title="Am Raster einrasten"><i class="bi bi-magnet"></i></button>
    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#grid-modal" title="Rastereinstellungen"><i class="bi bi-layout-wtf"></i></button>

    <div class="ed-sep"></div>

    <!-- Layer order -->
    <button class="btn btn-outline-secondary" type="button" id="btn-bring-front" title="In den Vordergrund"><i class="bi bi-layer-forward"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-bring-forward" title="Eine Ebene nach vorne"><i class="bi bi-front"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-send-backward" title="Eine Ebene nach hinten"><i class="bi bi-back"></i></button>
    <button class="btn btn-outline-secondary" type="button" id="btn-send-back" title="In den Hintergrund"><i class="bi bi-layer-backward"></i></button>
    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#layers-modal" title="Ebenen"><i class="bi bi-layers"></i></button>

    <!-- hidden zoom-level span kept for JS compatibility -->
    <span id="zoom-level" class="d-none">100 %</span>
</nav>

<!-- ═══════════════════════════════════════════════════════
     LEFT DRAWER  (Symbols + Properties)
════════════════════════════════════════════════════════ -->
<aside class="ed-drawer bg-body" id="ed-drawer">
    <div class="p-2 border-bottom d-flex align-items-center gap-2">
        <h2 class="h6 mb-0 flex-grow-1">Symbolbibliothek</h2>
        <button class="btn btn-sm btn-outline-secondary" type="button" id="btn-close-drawer" title="Schließen"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="px-2 pt-2 pb-1 border-bottom">
        <div class="symbol-toolbar">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="symbol-search" placeholder="Symbole suchen">
            </div>
            <select class="form-select form-select-sm" id="library-category">
                <option value="">Kategorie auswählen</option>
            </select>
            <select class="form-select form-select-sm" id="library-subcategory">
                <option value="">Unterkategorie auswählen</option>
            </select>
        </div>
    </div>
    <div class="ed-drawer-scroll">
        <div class="p-2 border-bottom">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-semibold">Katalog</span>
                <span class="badge text-bg-secondary" id="symbol-count">0</span>
            </div>
            <div class="small text-body-secondary mb-2" id="library-context">Verkehrszeichen und Zusatzzeichen werden geladen…</div>
            <div class="symbol-list" id="symbol-grid">
                <div class="symbol-empty">Symbole werden geladen…</div>
            </div>
        </div>

        <!-- Properties panel -->
        <div class="p-2" id="props-panel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-semibold">Eigenschaften</span>
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
                    <label class="form-label small" for="prop-real-w">Breite (m)</label>
                    <input type="text" class="form-control form-control-sm" id="prop-real-w" readonly>
                </div>
                <div class="col-6">
                    <label class="form-label small" for="prop-real-h">Höhe (m)</label>
                    <input type="text" class="form-control form-control-sm" id="prop-real-h" readonly>
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

<!-- ═══════════════════════════════════════════════════════
     CANVAS  (nearly full screen)
════════════════════════════════════════════════════════ -->
<main class="ed-canvas-wrap" id="canvas-area">
    <div class="ed-canvas-stage" id="canvas-wrapper">
        <canvas id="plan-canvas"></canvas>
    </div>
</main>

<!-- ═══════════════════════════════════════════════════════
     STATUS BAR
════════════════════════════════════════════════════════ -->
<footer class="ed-statusbar bg-body">
    <span><i class="bi bi-crosshair me-1"></i><span id="status-x">X: 0</span> · <span id="status-y">Y: 0</span></span>
    <span><i class="bi bi-zoom-in me-1"></i><span id="zoom-level-footer">100 %</span></span>
    <span><i class="bi bi-rulers me-1"></i><span id="status-scale">Maßstab <?= View::e($planScale) ?></span></span>
    <span class="ms-auto text-body-secondary">Plan #<?= View::e($planId) ?></span>
</footer>

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


<div class="modal fade" id="symbol-info-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="symbol-info-title">Symbolinfo</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="small text-body-secondary mb-2" id="symbol-info-number"></div>
                <div id="symbol-info-description"></div>
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

    // Drawer toggle (symbol library)
    var drawer    = document.getElementById('ed-drawer');
    var canvasWrap = document.getElementById('canvas-area');
    function setDrawer(open) {
        if (!drawer || !canvasWrap) return;
        if (open) {
            drawer.classList.add('open');
            canvasWrap.classList.add('drawer-open');
        } else {
            drawer.classList.remove('open');
            canvasWrap.classList.remove('drawer-open');
        }
    }
    var btnToggleDrawer = document.getElementById('btn-toggle-drawer');
    if (btnToggleDrawer) {
        btnToggleDrawer.addEventListener('click', function () {
            setDrawer(!drawer.classList.contains('open'));
        });
    }
    var btnCloseDrawer = document.getElementById('btn-close-drawer');
    if (btnCloseDrawer) {
        btnCloseDrawer.addEventListener('click', function () { setDrawer(false); });
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
