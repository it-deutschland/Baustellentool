/**
 * Sonka Bau & Sonnenimmobilien - Multi Administration – Plan Editor
 * Built on Fabric.js 5.x  |  No build process required
 * @license MIT
 */

'use strict';

// ── State ────────────────────────────────────────────────────
const Editor = {
    canvas:        null,
    planId:        null,
    projectId:     null,
    modified:      false,
    gridEnabled:   false,
    snapEnabled:   true,
    snapThreshold: 10,
    gridSize:      20,
    rulers:        { h: null, v: null },
    activeTool:    'select',
    scale:         '1:500',
    history: {
        stack:   [],
        pointer: -1,
        maxSize: 50,
    },
    drawingLine:   null,
    isDrawing:     false,
    symbols:       [],
    symbolIndex:   new Map(),
    managedSync:   false,
};

const PX_PER_MM = 96 / 25.4;
const LIBRARY_CATEGORIES = [
    '01 Luftbild',
    '02 Lageplan',
    '03 Gebäude',
    '04 Fahrbahn',
    '05 Fahrstreifen',
    '06 Gehweg',
    '07 Radweg',
    '08 Verkehrszeichen',
    '09 Zusatzzeichen',
    '10 Leitbaken',
    '11 Verkehrseinrichtungen',
    '12 Baustelle',
    '13 Fahrzeuge',
    '14 Markierungen',
    '15 Beschriftung',
    '16 Maße',
    '17 Legende',
    '18 Hintergrund',
];

const GEOMETRY_GROUPS = [
    {
        id: 'verkehrsflaechen',
        label: 'Verkehrsflächen',
        items: [
            { id: 'fahrbahn', label: 'Fahrbahn', category: '04 Fahrbahn', subcategory: 'Fahrbahn', shape: 'rect', widthM: 24, heightM: 6, fill: 'rgba(73,80,87,.22)', stroke: '#495057' },
            { id: 'fahrstreifen', label: 'Fahrstreifen', category: '05 Fahrstreifen', subcategory: 'Fahrstreifen', shape: 'rect', widthM: 18, heightM: 3.25, fill: 'rgba(108,117,125,.12)', stroke: '#6c757d', dashed: [10, 6] },
            { id: 'mittelinsel', label: 'Mittelinsel', category: '04 Fahrbahn', subcategory: 'Mittelinsel', shape: 'rect', widthM: 12, heightM: 2.5, fill: 'rgba(255,193,7,.18)', stroke: '#b08900' },
            { id: 'gehweg', label: 'Gehweg', category: '06 Gehweg', subcategory: 'Gehweg', shape: 'rect', widthM: 15, heightM: 2.5, fill: 'rgba(248,249,250,.95)', stroke: '#adb5bd' },
            { id: 'radweg', label: 'Radweg', category: '07 Radweg', subcategory: 'Radweg', shape: 'rect', widthM: 15, heightM: 2.2, fill: 'rgba(13,110,253,.12)', stroke: '#0d6efd' },
            { id: 'seitenstreifen', label: 'Seitenstreifen', category: '04 Fahrbahn', subcategory: 'Seitenstreifen', shape: 'rect', widthM: 18, heightM: 2.5, fill: 'rgba(233,236,239,.9)', stroke: '#868e96', dashed: [8, 5] },
            { id: 'parkflaeche', label: 'Parkfläche', category: '04 Fahrbahn', subcategory: 'Parkfläche', shape: 'rect', widthM: 10, heightM: 5, fill: 'rgba(25,135,84,.10)', stroke: '#198754', dashed: [12, 6] },
        ],
    },
    {
        id: 'knotenpunkte',
        label: 'Knotenpunkte',
        items: [
            { id: 'kreuzung', label: 'Kreuzung', category: '04 Fahrbahn', subcategory: 'Kreuzung', shape: 'intersection', widthM: 14, heightM: 14, fill: 'rgba(73,80,87,.22)', stroke: '#495057' },
            { id: 'einmuendung', label: 'Einmündung', category: '04 Fahrbahn', subcategory: 'Einmündung', shape: 'tjunction', widthM: 14, heightM: 12, fill: 'rgba(73,80,87,.22)', stroke: '#495057' },
            { id: 'kreisverkehr', label: 'Kreisverkehr', category: '04 Fahrbahn', subcategory: 'Kreisverkehr', shape: 'roundabout', widthM: 12, heightM: 12, fill: 'rgba(73,80,87,.14)', stroke: '#495057' },
        ],
    },
    {
        id: 'baustelle',
        label: 'Baustelle & Führung',
        items: [
            { id: 'baustellenflaeche', label: 'Baustellenfläche', category: '12 Baustelle', subcategory: 'Baustellenfläche', shape: 'rect', widthM: 14, heightM: 8, fill: 'rgba(255,193,7,.18)', stroke: '#ff922b' },
            { id: 'sperrflaeche', label: 'Sperrfläche', category: '12 Baustelle', subcategory: 'Sperrfläche', shape: 'hatched-rect', widthM: 10, heightM: 4, fill: 'rgba(220,53,69,.06)', stroke: '#dc3545' },
            { id: 'absperrbereich', label: 'Absperrbereich', category: '12 Baustelle', subcategory: 'Absperrbereich', shape: 'rect', widthM: 10, heightM: 5, fill: 'rgba(255,243,205,.7)', stroke: '#fd7e14', dashed: [8, 4] },
            { id: 'umleitung', label: 'Umleitung', category: '12 Baustelle', subcategory: 'Umleitung', shape: 'arrow', widthM: 8, heightM: 2.5, fill: 'rgba(13,110,253,.10)', stroke: '#0d6efd' },
            { id: 'pfeile', label: 'Pfeile', category: '14 Markierungen', subcategory: 'Pfeile', shape: 'arrow', widthM: 6, heightM: 1.8, fill: 'rgba(255,255,255,.9)', stroke: '#212529' },
            { id: 'markierungen', label: 'Markierungen', category: '14 Markierungen', subcategory: 'Markierungen', shape: 'markings', widthM: 12, heightM: 0.8, fill: 'rgba(255,255,255,.95)', stroke: '#212529' },
        ],
    },
];

// ── Initialise ───────────────────────────────────────────────
function initEditor(planId, projectId, canvasData) {
    Editor.planId    = planId;
    Editor.projectId = projectId;
    Editor.scale     = document.getElementById('plan-scale')?.value || Editor.scale;

    // Create Fabric canvas
    const wrapper = document.getElementById('canvas-wrapper');
    Editor.canvas = new fabric.Canvas('plan-canvas', {
        backgroundColor: '#ffffff',
        preserveObjectStacking: true,
        selection: true,
        controlsAboveOverlay: true,
    });

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    // Load existing canvas data
    if (canvasData && canvasData !== 'null' && canvasData !== '') {
        try {
            Editor.managedSync = true;
            Editor.canvas.loadFromJSON(canvasData, () => {
                Editor.managedSync = false;
                relinkManagedLabels();
                Editor.canvas.renderAll();
                pushHistory();
            });
        } catch (e) {
            console.warn('Could not load canvas data:', e);
            Editor.managedSync = false;
            pushHistory();
        }
    } else {
        drawGrid();
        pushHistory();
    }

    bindCanvasEvents();
    bindToolbar();
    bindProperties();
    bindSymbolPanel();
    bindLayerPanel();
    bindKeyboard();
    loadSymbolLibrary();
    initGeometryToolbar();
    initRulers();
    startAutoSave();
}

function parseScaleDenominator(scale = Editor.scale) {
    const match = String(scale || '').match(/1:(\d+)/);
    return match ? Math.max(1, parseInt(match[1], 10)) : 500;
}

function realWorldMmToCanvasPx(realWorldMm, scale = Editor.scale) {
    return (realWorldMm / parseScaleDenominator(scale)) * PX_PER_MM;
}

function canvasPxToRealWorldMeters(px, scale = Editor.scale) {
    return ((px / PX_PER_MM) * parseScaleDenominator(scale)) / 1000;
}

function formatMeters(value) {
    return `${value.toFixed(value >= 10 ? 1 : 2)} m`;
}

function createObjectId(prefix = 'obj') {
    return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

// ── Canvas resize ─────────────────────────────────────────────
function resizeCanvas() {
    const area = document.getElementById('canvas-area');
    if (!area) return;
    const w = area.offsetWidth;
    const h = area.offsetHeight;
    Editor.canvas.setWidth(w);
    Editor.canvas.setHeight(h);
    Editor.canvas.renderAll();
    updateRulers();
}

// ── Grid ─────────────────────────────────────────────────────
function drawGrid() {
    if (!Editor.gridEnabled) return;
    const w = Editor.canvas.width;
    const h = Editor.canvas.height;
    const gs = Editor.gridSize;
    const lines = [];

    for (let x = 0; x <= w; x += gs) {
        lines.push(new fabric.Line([x, 0, x, h], {
            stroke: '#ddd', strokeWidth: 0.5, selectable: false,
            evented: false, excludeFromExport: true, data: { type: 'grid' },
        }));
    }
    for (let y = 0; y <= h; y += gs) {
        lines.push(new fabric.Line([0, y, w, y], {
            stroke: '#ddd', strokeWidth: 0.5, selectable: false,
            evented: false, excludeFromExport: true, data: { type: 'grid' },
        }));
    }
    lines.forEach(l => { Editor.canvas.add(l); Editor.canvas.sendToBack(l); });
    Editor.canvas.renderAll();
}

function clearGrid() {
    const objs = Editor.canvas.getObjects().filter(o => o.data?.type === 'grid');
    objs.forEach(o => Editor.canvas.remove(o));
}

function toggleGrid() {
    Editor.gridEnabled = !Editor.gridEnabled;
    clearGrid();
    if (Editor.gridEnabled) drawGrid();
    document.getElementById('btn-grid')?.classList.toggle('active', Editor.gridEnabled);
    Editor.canvas.renderAll();
}

// ── Snap to grid ─────────────────────────────────────────────
function snapToGrid(value) {
    if (!Editor.snapEnabled || !Editor.gridEnabled) return value;
    return Math.round(value / Editor.gridSize) * Editor.gridSize;
}

// ── Canvas events ─────────────────────────────────────────────
function bindCanvasEvents() {
    const c = Editor.canvas;

    c.on('object:moving', (e) => {
        if (!e.target || e.target.data?.role === 'geometry-label') return;
        if (Editor.snapEnabled && Editor.gridEnabled) {
            e.target.set({
                left: snapToGrid(e.target.left),
                top:  snapToGrid(e.target.top),
            });
        }
        syncManagedObject(e.target);
        Editor.modified = true;
    });

    c.on('object:scaling', e => syncManagedObject(e.target));
    c.on('object:rotating', e => syncManagedObject(e.target));

    c.on('object:modified', (e) => {
        syncManagedObject(e.target);
        Editor.modified = true;
        pushHistory();
        updateProperties();
    });

    c.on('object:added', (e) => {
        if (!Editor.managedSync) {
            if (e.target?.data?.type === 'plan-geometry') {
                ensureGeometryLabel(e.target);
            }
            syncManagedObject(e.target);
        }
        Editor.modified = true;
    });

    c.on('object:removed', (e) => {
        if (!e.target || Editor.managedSync) return;
        removeLinkedGeometryLabel(e.target);
        Editor.modified = true;
    });

    c.on('selection:created', updateProperties);
    c.on('selection:updated', updateProperties);
    c.on('selection:cleared', clearProperties);

    c.on('mouse:move', (e) => {
        const ptr = c.getPointer(e.e);
        updateStatusBar(ptr.x, ptr.y);
        updateRulerCursor(ptr.x, ptr.y);
    });

    // Line drawing tool
    c.on('mouse:down', (e) => {
        if (Editor.activeTool === 'line') {
            const ptr = c.getPointer(e.e);
            Editor.isDrawing = true;
            Editor.drawingLine = new fabric.Line(
                [ptr.x, ptr.y, ptr.x, ptr.y],
                { stroke: '#333', strokeWidth: 2, selectable: false }
            );
            c.add(Editor.drawingLine);
        } else if (Editor.activeTool === 'arrow') {
            const ptr = c.getPointer(e.e);
            Editor.isDrawing = true;
            Editor.drawingLine = new fabric.Line(
                [ptr.x, ptr.y, ptr.x, ptr.y],
                { stroke: '#333', strokeWidth: 2, selectable: false }
            );
            c.add(Editor.drawingLine);
        }
    });

    c.on('mouse:move', (e) => {
        if (!Editor.isDrawing || !Editor.drawingLine) return;
        const ptr = c.getPointer(e.e);
        Editor.drawingLine.set({ x2: ptr.x, y2: ptr.y });
        c.renderAll();
    });

    c.on('mouse:up', () => {
        if (Editor.isDrawing && Editor.drawingLine) {
            if (Editor.activeTool === 'arrow') {
                const arrow = convertLineToArrow(Editor.drawingLine);
                c.remove(Editor.drawingLine);
                c.add(arrow);
                c.setActiveObject(arrow);
            } else {
                Editor.drawingLine.set({ selectable: true });
            }
            Editor.isDrawing = false;
            Editor.drawingLine = null;
            setTool('select');
            pushHistory();
        }
    });
}

// ── Tools ─────────────────────────────────────────────────────
function setTool(tool) {
    Editor.activeTool = tool;
    const c = Editor.canvas;

    document.querySelectorAll('.tool-btn[data-tool]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tool === tool);
    });

    switch (tool) {
        case 'select':
            c.isDrawingMode = false;
            c.selection = true;
            c.defaultCursor = 'default';
            break;
        case 'pan':
            c.isDrawingMode = false;
            c.selection = false;
            c.defaultCursor = 'grab';
            break;
        case 'pencil':
            c.isDrawingMode = true;
            c.freeDrawingBrush.color = getActiveColor();
            c.freeDrawingBrush.width = 2;
            break;
        case 'line':
        case 'arrow':
            c.isDrawingMode = false;
            c.selection = false;
            c.defaultCursor = 'crosshair';
            break;
        case 'rect':
            addShape('rect');
            setTool('select');
            break;
        case 'circle':
            addShape('circle');
            setTool('select');
            break;
        case 'text':
            addText();
            setTool('select');
            break;
    }
}

function addShape(type) {
    const c = Editor.canvas;
    const cx = c.width / 2;
    const cy = c.height / 2;
    let shape;

    if (type === 'rect') {
        shape = new fabric.Rect({
            left: cx - 50, top: cy - 30, width: 100, height: 60,
            fill: 'rgba(13,110,253,.1)', stroke: '#0d6efd', strokeWidth: 2,
        });
    } else if (type === 'circle') {
        shape = new fabric.Circle({
            left: cx - 40, top: cy - 40, radius: 40,
            fill: 'rgba(25,135,84,.1)', stroke: '#198754', strokeWidth: 2,
        });
    }

    if (shape) {
        c.add(shape);
        c.setActiveObject(shape);
        pushHistory();
    }
}

function addText() {
    const c = Editor.canvas;
    const t = new fabric.IText('Text', {
        left: c.width / 2 - 40, top: c.height / 2 - 12,
        fontFamily: 'Helvetica', fontSize: 16, fill: '#333',
    });
    c.add(t);
    c.setActiveObject(t);
    t.enterEditing();
    pushHistory();
}

function findGeometryTemplate(templateId) {
    for (const group of GEOMETRY_GROUPS) {
        const item = group.items.find(entry => entry.id === templateId);
        if (item) return item;
    }
    return null;
}

function initGeometryToolbar() {
    const categorySelect = document.getElementById('geometry-category');
    const templateSelect = document.getElementById('geometry-template');
    if (!categorySelect || !templateSelect) return;

    categorySelect.innerHTML = '<option value="">Planeditor-Kategorie</option>';
    GEOMETRY_GROUPS.forEach(group => {
        const option = document.createElement('option');
        option.value = group.id;
        option.textContent = group.label;
        categorySelect.appendChild(option);
    });

    categorySelect.addEventListener('change', () => populateGeometryTemplates(categorySelect.value));
    templateSelect.addEventListener('change', () => updateLibraryContext());
    document.getElementById('btn-add-geometry')?.addEventListener('click', () => {
        if (templateSelect.value) addGeometryTemplate(templateSelect.value);
    });
    populateGeometryTemplates(GEOMETRY_GROUPS[0]?.id || '');
}

function populateGeometryTemplates(groupId) {
    const categorySelect = document.getElementById('geometry-category');
    const templateSelect = document.getElementById('geometry-template');
    if (!templateSelect) return;
    const group = GEOMETRY_GROUPS.find(entry => entry.id === groupId) || GEOMETRY_GROUPS[0];
    if (categorySelect && group) categorySelect.value = group.id;
    templateSelect.innerHTML = '<option value="">Element auswählen</option>';
    if (!group) return;

    group.items.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.category} · ${item.label}`;
        templateSelect.appendChild(option);
    });

    if (group.items[0]) {
        templateSelect.value = group.items[0].id;
    }
    updateLibraryContext();
}

function addGeometryTemplate(templateId) {
    const def = findGeometryTemplate(templateId);
    if (!def) return;

    const widthPx = realWorldMmToCanvasPx(def.widthM * 1000);
    const heightPx = realWorldMmToCanvasPx(def.heightM * 1000);
    const left = Math.max(40, Editor.canvas.width / 2 - widthPx / 2);
    const top = Math.max(40, Editor.canvas.height / 2 - heightPx / 2);
    const objectId = createObjectId('geometry');
    const shape = buildGeometryShape(def, left, top, widthPx, heightPx, objectId);
    if (!shape) return;

    shape.set({
        left,
        top,
        data: {
            ...(shape.data || {}),
            id: objectId,
            type: 'plan-geometry',
            geometryTemplate: def.id,
            geometryName: def.label,
            editorCategory: def.category,
            subcategory: def.subcategory,
            autoScale: true,
            baseWidthPx: widthPx,
            baseHeightPx: heightPx,
            isMeasurable: true,
        },
    });

    Editor.canvas.add(shape);
    ensureGeometryLabel(shape);
    Editor.canvas.setActiveObject(shape);
    Editor.canvas.renderAll();
    pushHistory();
}

function buildGeometryShape(def, left, top, widthPx, heightPx, objectId) {
    const common = {
        left,
        top,
        fill: def.fill,
        stroke: def.stroke,
        strokeWidth: 2,
        strokeDashArray: def.dashed || null,
        data: { id: objectId },
    };

    if (def.shape === 'rect') {
        return new fabric.Rect({ ...common, width: widthPx, height: heightPx });
    }
    if (def.shape === 'hatched-rect') {
        const base = new fabric.Rect({ width: widthPx, height: heightPx, fill: def.fill, stroke: def.stroke, strokeWidth: 2 });
        const lines = [];
        for (let offset = -heightPx; offset < widthPx; offset += 12) {
            lines.push(new fabric.Line([offset, heightPx, offset + heightPx, 0], { stroke: def.stroke, strokeWidth: 1 }));
        }
        return new fabric.Group([base, ...lines], common);
    }
    if (def.shape === 'arrow') {
        const shaft = heightPx / 2.6;
        const points = [
            { x: 0, y: shaft },
            { x: widthPx * 0.62, y: shaft },
            { x: widthPx * 0.62, y: 0 },
            { x: widthPx, y: heightPx / 2 },
            { x: widthPx * 0.62, y: heightPx },
            { x: widthPx * 0.62, y: heightPx - shaft },
            { x: 0, y: heightPx - shaft },
        ];
        return new fabric.Polygon(points, { ...common, objectCaching: false });
    }
    if (def.shape === 'markings') {
        const lines = [];
        const step = Math.max(16, widthPx / 6);
        for (let x = 0; x < widthPx; x += step) {
            lines.push(new fabric.Line([x, heightPx / 2, Math.min(x + step / 2, widthPx), heightPx / 2], { stroke: def.stroke, strokeWidth: 3 }));
        }
        return new fabric.Group(lines, common);
    }
    if (def.shape === 'intersection') {
        const horizontal = new fabric.Rect({ left: 0, top: heightPx * 0.33, width: widthPx, height: heightPx * 0.34, fill: def.fill, stroke: def.stroke, strokeWidth: 2 });
        const vertical = new fabric.Rect({ left: widthPx * 0.33, top: 0, width: widthPx * 0.34, height: heightPx, fill: def.fill, stroke: def.stroke, strokeWidth: 2 });
        return new fabric.Group([horizontal, vertical], common);
    }
    if (def.shape === 'tjunction') {
        const horizontal = new fabric.Rect({ left: 0, top: 0, width: widthPx, height: heightPx * 0.34, fill: def.fill, stroke: def.stroke, strokeWidth: 2 });
        const vertical = new fabric.Rect({ left: widthPx * 0.33, top: 0, width: widthPx * 0.34, height: heightPx, fill: def.fill, stroke: def.stroke, strokeWidth: 2 });
        return new fabric.Group([horizontal, vertical], common);
    }
    if (def.shape === 'roundabout') {
        const outer = new fabric.Circle({ left: 0, top: 0, radius: Math.min(widthPx, heightPx) / 2, fill: 'transparent', stroke: def.stroke, strokeWidth: 8 });
        const island = new fabric.Circle({ left: widthPx * 0.28, top: heightPx * 0.28, radius: Math.min(widthPx, heightPx) * 0.22, fill: 'rgba(255,193,7,.18)', stroke: '#b08900', strokeWidth: 2 });
        return new fabric.Group([outer, island], common);
    }
    return null;
}

function convertLineToArrow(line) {
    const { x1, y1, x2, y2 } = line;
    const angle = Math.atan2(y2 - y1, x2 - x1);
    const headLength = 16;
    const points = [
        { x: x1, y: y1 },
        { x: x2, y: y2 },
        { x: x2 - headLength * Math.cos(angle - Math.PI / 6), y: y2 - headLength * Math.sin(angle - Math.PI / 6) },
        { x: x2, y: y2 },
        { x: x2 - headLength * Math.cos(angle + Math.PI / 6), y: y2 - headLength * Math.sin(angle + Math.PI / 6) },
    ];
    return new fabric.Polyline(points, {
        fill: '',
        stroke: '#333',
        strokeWidth: 2,
        data: { type: 'arrow-line' },
    });
}

function ensureGeometryLabel(object) {
    if (!object || object.data?.type !== 'plan-geometry') return;
    let label = findLinkedGeometryLabel(object);
    if (!label) {
        Editor.managedSync = true;
        label = new fabric.Text('', {
            fontSize: 12,
            fill: '#495057',
            selectable: false,
            evented: false,
            hoverCursor: 'default',
            data: {
                role: 'geometry-label',
                parentId: object.data.id,
            },
        });
        Editor.canvas.add(label);
        Editor.managedSync = false;
    }
    syncManagedObject(object);
}

function findLinkedGeometryLabel(object) {
    const parentId = object?.data?.id;
    if (!parentId) return null;
    return Editor.canvas.getObjects().find(item => item.data?.role === 'geometry-label' && item.data?.parentId === parentId) || null;
}

function removeLinkedGeometryLabel(object) {
    if (!object || object.data?.role === 'geometry-label') return;
    const label = findLinkedGeometryLabel(object);
    if (!label) return;
    Editor.managedSync = true;
    Editor.canvas.remove(label);
    Editor.managedSync = false;
}

function relinkManagedLabels() {
    const seenLabels = new Set();
    Editor.canvas.getObjects().forEach(object => {
        if (object.data?.type === 'plan-geometry') {
            ensureGeometryLabel(object);
            const label = findLinkedGeometryLabel(object);
            if (label) seenLabels.add(label);
        }
    });
    Editor.canvas.getObjects()
        .filter(object => object.data?.role === 'geometry-label' && !seenLabels.has(object))
        .forEach(label => Editor.canvas.remove(label));
}

function syncManagedObject(object) {
    if (!object || object.data?.role === 'geometry-label') return;
    if (object.data?.type !== 'plan-geometry') return;

    const label = findLinkedGeometryLabel(object);
    if (!label) return;

    const widthPx = (object.data.baseWidthPx || object.getScaledWidth()) * (object.scaleX || 1);
    const heightPx = (object.data.baseHeightPx || object.getScaledHeight()) * (object.scaleY || 1);
    const widthM = canvasPxToRealWorldMeters(widthPx);
    const heightM = canvasPxToRealWorldMeters(heightPx);
    const bounds = object.getBoundingRect();

    Editor.managedSync = true;
    label.set({
        text: `${formatMeters(widthM)} × ${formatMeters(heightM)}`,
        left: bounds.left + bounds.width / 2 - label.getScaledWidth() / 2,
        top: bounds.top + bounds.height + 8,
    });
    label.setCoords();
    label.moveTo(object.getObjectScaling ? object.canvas.getObjects().indexOf(object) + 1 : Editor.canvas.getObjects().length - 1);
    Editor.managedSync = false;
    if (Editor.canvas) Editor.canvas.renderAll();
}

// ── Zoom ──────────────────────────────────────────────────────
function zoomIn()  { setZoom(Editor.canvas.getZoom() * 1.2); }
function zoomOut() { setZoom(Editor.canvas.getZoom() / 1.2); }
function zoomReset() { setZoom(1); Editor.canvas.viewportTransform = [1,0,0,1,0,0]; Editor.canvas.renderAll(); }

function setZoom(zoom) {
    zoom = Math.min(Math.max(zoom, 0.05), 20);
    Editor.canvas.setZoom(zoom);
    Editor.canvas.renderAll();
    document.getElementById('zoom-level').textContent = Math.round(zoom * 100) + '%';
    const footerZoom = document.getElementById('zoom-level-footer');
    if (footerZoom) footerZoom.textContent = Math.round(zoom * 100) + ' %';
}

// Handle scroll-to-zoom
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('canvas-area')?.addEventListener('wheel', (e) => {
        e.preventDefault();
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        setZoom(Editor.canvas.getZoom() * delta);
    }, { passive: false });

    // Show note after credits field is filled
    const creditsInput = document.getElementById('exportCredits');
    const creditsNote  = document.getElementById('exportCreditsNote');
    if (creditsInput && creditsNote) {
        creditsInput.addEventListener('input', function () {
            creditsNote.classList.toggle('d-none', this.value.trim() === '');
        });
    }

    // Handle export buttons in export modal
    document.getElementById('btn-export-svg')?.addEventListener('click', exportSvg);
    document.getElementById('btn-export-png')?.addEventListener('click', exportPng);
    document.getElementById('btn-export-pdf')?.addEventListener('click', exportPdf);
});

// ── Align ─────────────────────────────────────────────────────
function alignObjects(direction) {
    const objs = Editor.canvas.getActiveObjects();
    if (objs.length < 2) return;

    const bounds = objs.map(o => o.getBoundingRect());
    const minX = Math.min(...bounds.map(b => b.left));
    const minY = Math.min(...bounds.map(b => b.top));
    const maxX = Math.max(...bounds.map(b => b.left + b.width));
    const maxY = Math.max(...bounds.map(b => b.top + b.height));

    objs.forEach((o, i) => {
        const b = bounds[i];
        switch (direction) {
            case 'left':   o.set('left', minX); break;
            case 'right':  o.set('left', maxX - b.width); break;
            case 'top':    o.set('top',  minY); break;
            case 'bottom': o.set('top',  maxY - b.height); break;
            case 'centerH': o.set('left', (minX + maxX) / 2 - b.width / 2); break;
            case 'centerV': o.set('top',  (minY + maxY) / 2 - b.height / 2); break;
        }
        o.setCoords();
    });

    Editor.canvas.renderAll();
    pushHistory();
}

// ── Group/Ungroup ─────────────────────────────────────────────
function groupSelected() {
    const sel = Editor.canvas.getActiveObject();
    if (!sel || sel.type !== 'activeSelection') return;
    const group = sel.toGroup();
    Editor.canvas.setActiveObject(group);
    pushHistory();
}

function ungroupSelected() {
    const sel = Editor.canvas.getActiveObject();
    if (!sel || sel.type !== 'group') return;
    sel.toActiveSelection();
    pushHistory();
}

// ── Copy / Paste ──────────────────────────────────────────────
let _clipboard = null;

function copySelected() {
    Editor.canvas.getActiveObject()?.clone(cloned => { _clipboard = cloned; });
}

function pasteClipboard() {
    if (!_clipboard) return;
    _clipboard.clone(cloned => {
        Editor.canvas.discardActiveObject();
        cloned.set({ left: cloned.left + 20, top: cloned.top + 20, evented: true });
        if (cloned.type === 'activeSelection') {
            cloned.canvas = Editor.canvas;
            cloned.forEachObject(o => Editor.canvas.add(o));
            cloned.setCoords();
        } else {
            Editor.canvas.add(cloned);
        }
        Editor.canvas.setActiveObject(cloned);
        Editor.canvas.requestRenderAll();
        pushHistory();
        _clipboard.set({ left: cloned.left, top: cloned.top });
    });
}

// ── Mirror ────────────────────────────────────────────────────
function mirrorH() {
    const obj = Editor.canvas.getActiveObject();
    if (!obj) return;
    obj.set('flipX', !obj.flipX);
    Editor.canvas.renderAll();
    pushHistory();
}

function mirrorV() {
    const obj = Editor.canvas.getActiveObject();
    if (!obj) return;
    obj.set('flipY', !obj.flipY);
    Editor.canvas.renderAll();
    pushHistory();
}

// ── Delete ────────────────────────────────────────────────────
function deleteSelected() {
    const obj = Editor.canvas.getActiveObject();
    if (!obj) return;
    if (obj.type === 'activeSelection') {
        obj.forEachObject(o => Editor.canvas.remove(o));
        Editor.canvas.discardActiveObject();
    } else {
        Editor.canvas.remove(obj);
    }
    Editor.canvas.renderAll();
    pushHistory();
}

// ── Undo / Redo ───────────────────────────────────────────────
function pushHistory() {
    const json = JSON.stringify(Editor.canvas.toJSON(['data', 'id']));
    // Trim future if we branched
    Editor.history.stack = Editor.history.stack.slice(0, Editor.history.pointer + 1);
    Editor.history.stack.push(json);
    if (Editor.history.stack.length > Editor.history.maxSize) {
        Editor.history.stack.shift();
    }
    Editor.history.pointer = Editor.history.stack.length - 1;
    updateHistoryButtons();
}

function undo() {
    if (Editor.history.pointer <= 0) return;
    Editor.history.pointer--;
    restoreHistory(Editor.history.stack[Editor.history.pointer]);
}

function redo() {
    if (Editor.history.pointer >= Editor.history.stack.length - 1) return;
    Editor.history.pointer++;
    restoreHistory(Editor.history.stack[Editor.history.pointer]);
}

function restoreHistory(json) {
    Editor.managedSync = true;
    Editor.canvas.loadFromJSON(json, () => {
        Editor.managedSync = false;
        relinkManagedLabels();
        Editor.canvas.renderAll();
        updateHistoryButtons();
        Editor.modified = true;
    });
}

function updateHistoryButtons() {
    const undoBtn = document.getElementById('btn-undo');
    const redoBtn = document.getElementById('btn-redo');
    if (undoBtn) undoBtn.disabled = Editor.history.pointer <= 0;
    if (redoBtn) redoBtn.disabled = Editor.history.pointer >= Editor.history.stack.length - 1;
}

// ── Properties panel ─────────────────────────────────────────
function updateProperties() {
    const obj = Editor.canvas.getActiveObject();
    if (!obj) { clearProperties(); return; }

    const panel = document.getElementById('props-panel');
    if (!panel) return;
    panel.style.display = 'block';

    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val ?? '';
    };

    set('prop-x',       Math.round(obj.left));
    set('prop-y',       Math.round(obj.top));
    set('prop-w',       Math.round(obj.getScaledWidth()));
    set('prop-h',       Math.round(obj.getScaledHeight()));
    set('prop-real-w',  formatMeters(canvasPxToRealWorldMeters(obj.getScaledWidth())));
    set('prop-real-h',  formatMeters(canvasPxToRealWorldMeters(obj.getScaledHeight())));
    set('prop-rot',     Math.round(obj.angle));
    set('prop-opacity', Math.round((obj.opacity ?? 1) * 100));

    const colorEl = document.getElementById('prop-fill');
    if (colorEl && obj.fill && typeof obj.fill === 'string' && obj.fill.startsWith('#')) {
        colorEl.value = obj.fill;
    }

    const strokeEl = document.getElementById('prop-stroke');
    if (strokeEl && obj.stroke && typeof obj.stroke === 'string' && obj.stroke.startsWith('#')) {
        strokeEl.value = obj.stroke;
    }
}

function clearProperties() {
    const panel = document.getElementById('props-panel');
    if (panel) panel.style.display = 'none';
    ['prop-real-w', 'prop-real-h'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
}

function bindProperties() {
    const c = Editor.canvas;
    const bindProp = (id, setProp, transform = v => v) => {
        document.getElementById(id)?.addEventListener('input', function () {
            const obj = c.getActiveObject();
            if (!obj) return;
            obj.set(setProp, transform(parseFloat(this.value)));
            syncManagedObject(obj);
            c.renderAll();
        });
    };

    bindProp('prop-x',       'left');
    bindProp('prop-y',       'top');
    bindProp('prop-rot',     'angle');
    bindProp('prop-opacity', 'opacity', v => v / 100);

    document.getElementById('prop-w')?.addEventListener('input', function () {
        const obj = c.getActiveObject();
        if (!obj) return;
        const scale = parseFloat(this.value) / obj.width;
        obj.scaleX = scale;
        syncManagedObject(obj);
        c.renderAll();
    });

    document.getElementById('prop-h')?.addEventListener('input', function () {
        const obj = c.getActiveObject();
        if (!obj) return;
        const scale = parseFloat(this.value) / obj.height;
        obj.scaleY = scale;
        syncManagedObject(obj);
        c.renderAll();
    });

    document.getElementById('prop-fill')?.addEventListener('input', function () {
        const obj = c.getActiveObject();
        if (!obj) return;
        obj.set('fill', this.value);
        c.renderAll();
    });

    document.getElementById('prop-stroke')?.addEventListener('input', function () {
        const obj = c.getActiveObject();
        if (!obj) return;
        obj.set('stroke', this.value);
        c.renderAll();
    });
}

// ── Toolbar binding ───────────────────────────────────────────
function bindToolbar() {
    // Tool buttons
    document.querySelectorAll('.tool-btn[data-tool]').forEach(btn => {
        btn.addEventListener('click', () => setTool(btn.dataset.tool));
    });

    document.getElementById('btn-zoom-in')?.addEventListener('click',    zoomIn);
    document.getElementById('btn-zoom-out')?.addEventListener('click',   zoomOut);
    document.getElementById('btn-zoom-reset')?.addEventListener('click', zoomReset);
    document.getElementById('btn-grid')?.addEventListener('click',       toggleGrid);
    document.getElementById('btn-snap')?.addEventListener('click',       () => {
        Editor.snapEnabled = !Editor.snapEnabled;
        document.getElementById('btn-snap').classList.toggle('active', Editor.snapEnabled);
    });

    document.getElementById('btn-undo')?.addEventListener('click', undo);
    document.getElementById('btn-redo')?.addEventListener('click', redo);

    document.getElementById('btn-group')?.addEventListener('click',   groupSelected);
    document.getElementById('btn-ungroup')?.addEventListener('click', ungroupSelected);
    document.getElementById('btn-copy')?.addEventListener('click',    copySelected);
    document.getElementById('btn-paste')?.addEventListener('click',   pasteClipboard);
    document.getElementById('btn-delete')?.addEventListener('click',  deleteSelected);
    document.getElementById('btn-mirror-h')?.addEventListener('click', mirrorH);
    document.getElementById('btn-mirror-v')?.addEventListener('click', mirrorV);

    document.getElementById('btn-align-left')?.addEventListener('click',    () => alignObjects('left'));
    document.getElementById('btn-align-right')?.addEventListener('click',   () => alignObjects('right'));
    document.getElementById('btn-align-top')?.addEventListener('click',     () => alignObjects('top'));
    document.getElementById('btn-align-bottom')?.addEventListener('click',  () => alignObjects('bottom'));
    document.getElementById('btn-align-centerH')?.addEventListener('click', () => alignObjects('centerH'));
    document.getElementById('btn-align-centerV')?.addEventListener('click', () => alignObjects('centerV'));

    document.getElementById('btn-save')?.addEventListener('click', savePlan);
    document.getElementById('btn-export')?.addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('export-modal'));
        modal.show();
    });

    document.getElementById('btn-export-svg')?.addEventListener('click', exportSvg);
    document.getElementById('btn-export-png')?.addEventListener('click', exportPng);
    document.getElementById('btn-export-pdf')?.addEventListener('click', exportPdf);

    document.getElementById('plan-scale')?.addEventListener('change', function () {
        const previousScale = Editor.scale;
        Editor.scale = this.value;
        rescaleManagedObjects(previousScale, Editor.scale);
        document.getElementById('status-scale').textContent = 'Maßstab ' + this.value;
        updateProperties();
        updateLibraryContext();
    });

    // Image upload from file
    document.getElementById('btn-add-image')?.addEventListener('click', () => {
        document.getElementById('img-upload-input')?.click();
    });

    document.getElementById('img-upload-input')?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            fabric.Image.fromURL(e.target.result, (img) => {
                img.scaleToWidth(Math.min(img.width, 200));
                img.set({ left: 50, top: 50 });
                Editor.canvas.add(img);
                Editor.canvas.setActiveObject(img);
                pushHistory();
            });
        };
        reader.readAsDataURL(file);
        this.value = '';
    });
}

// ── Symbol library panel ──────────────────────────────────────
function bindSymbolPanel() {
    const searchInput = document.getElementById('symbol-search');
    searchInput?.addEventListener('input', function () {
        filterSymbols();
    });

    document.getElementById('library-category')?.addEventListener('change', () => {
        populateLibrarySubcategories();
        filterSymbols();
    });
    document.getElementById('library-subcategory')?.addEventListener('change', filterSymbols);
}

async function loadSymbolLibrary() {
    const grid = document.getElementById('symbol-grid');
    if (!grid) return;

    try {
        const response = await fetch('/api/v1/symbols');
        const data     = await response.json();
        const symbols  = Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
        Editor.symbols = symbols;
        Editor.symbolIndex = new Map(symbols.map(symbol => [String(symbol.id), symbol]));
        populateLibraryCategories();
        filterSymbols();

        // Canvas drop target
        document.getElementById('canvas-area')?.addEventListener('dragover', e => e.preventDefault());
        document.getElementById('canvas-area')?.addEventListener('drop', e => {
            e.preventDefault();
            const symbolId = e.dataTransfer.getData('sym-id');
            const symbol = Editor.symbolIndex.get(symbolId);
            if (!symbol) return;
            const rect = Editor.canvas.getElement().getBoundingClientRect();
            const x    = (e.clientX - rect.left) / Editor.canvas.getZoom();
            const y    = (e.clientY - rect.top) / Editor.canvas.getZoom();
            addSymbolToCanvas(symbol, x, y);
        });

    } catch (e) {
        console.error('Error loading symbol library:', e);
        grid.innerHTML = '<div class="symbol-empty">Symbolbibliothek konnte nicht geladen werden.</div>';
    }
}

function populateLibraryCategories() {
    const categorySelect = document.getElementById('library-category');
    if (!categorySelect) return;

    categorySelect.innerHTML = '<option value="">Kategorie auswählen</option>';
    LIBRARY_CATEGORIES.forEach(category => {
        const option = document.createElement('option');
        option.value = category;
        option.textContent = category;
        categorySelect.appendChild(option);
    });
    categorySelect.value = '08 Verkehrszeichen';
    populateLibrarySubcategories();
}

function populateLibrarySubcategories() {
    const category = document.getElementById('library-category')?.value || '';
    const subcategorySelect = document.getElementById('library-subcategory');
    if (!subcategorySelect) return;

    subcategorySelect.innerHTML = '<option value="">Unterkategorie auswählen</option>';
    const subcategories = [...new Set(Editor.symbols
        .filter(symbol => (symbol.editor_category || '') === category)
        .map(symbol => symbol.subcategory || '')
        .filter(Boolean))]
        .sort((left, right) => left.localeCompare(right, 'de'));

    subcategories.forEach(subcategory => {
        const option = document.createElement('option');
        option.value = subcategory;
        option.textContent = subcategory;
        subcategorySelect.appendChild(option);
    });
    updateLibraryContext();
}

function addSymbolToCanvas(symbol, centerX = null, centerY = null) {
    const url = symbol.file_url || symbol.file_path;
    const widthPx = realWorldMmToCanvasPx(parseFloat(symbol.width_mm) || 600);
    const heightPx = realWorldMmToCanvasPx(parseFloat(symbol.height_mm) || 600);
    centerX = centerX ?? (Editor.canvas.width / 2);
    centerY = centerY ?? (Editor.canvas.height / 2);
    addImageToCanvas(url, centerX - widthPx / 2, centerY - heightPx / 2, widthPx, symbol);
}

function addImageToCanvas(url, x, y, targetWidth, symbol = null) {
    fabric.Image.fromURL(url, (img) => {
        if (!img.width) return;
        img.scaleToWidth(targetWidth);
        img.set({
            left: x,
            top: y,
            data: symbol ? {
                id: symbol.id,
                type: 'symbol',
                autoScale: true,
                isMeasurable: true,
                symbolNumber: symbol.sign_number || '',
                symbolName: symbol.name || '',
                widthMm: parseFloat(symbol.width_mm) || null,
                heightMm: parseFloat(symbol.height_mm) || null,
                editorCategory: symbol.editor_category || '',
                subcategory: symbol.subcategory || '',
                source: symbol.source || '',
            } : img.data,
        });
        Editor.canvas.add(img);
        Editor.canvas.setActiveObject(img);
        Editor.canvas.renderAll();
        pushHistory();
    }, { crossOrigin: 'anonymous' });
}

function filterSymbols() {
    const grid = document.getElementById('symbol-grid');
    if (!grid) return;

    const query = (document.getElementById('symbol-search')?.value || '').trim().toLowerCase();
    const category = document.getElementById('library-category')?.value || '';
    const subcategory = document.getElementById('library-subcategory')?.value || '';
    const filtered = Editor.symbols.filter(symbol => {
        const haystack = `${symbol.sign_number || ''} ${symbol.name || ''} ${symbol.description || ''} ${symbol.tags || ''}`.toLowerCase();
        const matchQuery = query === '' || haystack.includes(query);
        const matchCategory = category === '' || (symbol.editor_category || '') === category;
        const matchSubcategory = subcategory === '' || (symbol.subcategory || '') === subcategory;
        return matchQuery && matchCategory && matchSubcategory;
    });

    renderLibrarySymbols(filtered);
}

function renderLibrarySymbols(symbols) {
    const grid = document.getElementById('symbol-grid');
    const count = document.getElementById('symbol-count');
    if (!grid || !count) return;

    count.textContent = String(symbols.length);
    grid.innerHTML = '';

    if (symbols.length === 0) {
        grid.innerHTML = '<div class="symbol-empty">Keine Symbole in dieser Kategorie. Geometrische Straßenbauelemente fügen Sie über die Topbar ein.</div>';
        updateLibraryContext();
        return;
    }

    symbols.forEach(symbol => {
        const item = document.createElement('div');
        item.className = 'symbol-list-item';
        item.draggable = true;
        item.innerHTML = `
            <img src="${escHtml(symbol.file_url || symbol.file_path || '')}" alt="${escHtml(symbol.name || '')}" class="symbol-preview" onerror="this.src='/assets/img/symbol-placeholder.svg'">
            <div class="symbol-meta">
                <div class="symbol-number">${escHtml(symbol.sign_number || '–')}</div>
                <div class="symbol-name">${escHtml(symbol.name || '')}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-action="info" title="Beschreibung"><i class="bi bi-info-circle"></i></button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-action="add" title="In Plan einfügen"><i class="bi bi-plus-lg"></i></button>
        `;
        item.addEventListener('dragstart', event => {
            event.dataTransfer.setData('sym-id', String(symbol.id));
        });
        item.querySelector('[data-action="add"]')?.addEventListener('click', () => addSymbolToCanvas(symbol));
        item.querySelector('[data-action="info"]')?.addEventListener('click', () => showSymbolInfo(symbol));
        item.addEventListener('dblclick', () => addSymbolToCanvas(symbol));
        grid.appendChild(item);
    });
    updateLibraryContext();
}

function updateLibraryContext() {
    const category = document.getElementById('library-category')?.value || '';
    const subcategory = document.getElementById('library-subcategory')?.value || '';
    const geometryCategory = document.getElementById('geometry-category');
    const geometryTemplate = document.getElementById('geometry-template');
    const context = document.getElementById('library-context');
    if (!context) return;

    const parts = [];
    if (category) parts.push(category);
    if (subcategory) parts.push(subcategory);
    if (parts.length > 0) {
        context.textContent = parts.join(' › ');
        return;
    }

    if (geometryCategory?.value && geometryTemplate?.value) {
        const def = findGeometryTemplate(geometryTemplate.value);
        if (def) {
            context.textContent = `${def.category} › ${def.label} wird über die Topbar eingefügt und automatisch mit Maßangaben versehen.`;
            return;
        }
    }

    context.textContent = 'Verkehrszeichen und Zusatzzeichen mit Nummer, Vorschau und Detailinfo.';
}

function showSymbolInfo(symbol) {
    document.getElementById('symbol-info-title').textContent = symbol.name || 'Symbolinfo';
    document.getElementById('symbol-info-number').textContent = symbol.sign_number ? `Nummer ${symbol.sign_number}` : 'Ohne Nummer';
    document.getElementById('symbol-info-description').textContent = symbol.description || symbol.name || 'Keine Beschreibung vorhanden.';
    const modalEl = document.getElementById('symbol-info-modal');
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

function rescaleManagedObjects(previousScale, nextScale) {
    const previous = parseScaleDenominator(previousScale);
    const next = parseScaleDenominator(nextScale);
    if (previous === next) return;

    const factor = previous / next;
    Editor.managedSync = true;
    Editor.canvas.getObjects().forEach(object => {
        if (object.data?.role === 'geometry-label') return;
        if (!object.data?.autoScale) return;
        object.scaleX = (object.scaleX || 1) * factor;
        object.scaleY = (object.scaleY || 1) * factor;
        object.setCoords();
        syncManagedObject(object);
    });
    Editor.managedSync = false;
    Editor.canvas.renderAll();
    Editor.modified = true;
}

// ── Layer panel ───────────────────────────────────────────────
function bindLayerPanel() {
    document.getElementById('btn-bring-forward')?.addEventListener('click',  () => { Editor.canvas.getActiveObject()?.bringForward();  Editor.canvas.renderAll(); pushHistory(); });
    document.getElementById('btn-send-backward')?.addEventListener('click',  () => { Editor.canvas.getActiveObject()?.sendBackwards(); Editor.canvas.renderAll(); pushHistory(); });
    document.getElementById('btn-bring-front')?.addEventListener('click',    () => { Editor.canvas.getActiveObject()?.bringToFront();  Editor.canvas.renderAll(); pushHistory(); });
    document.getElementById('btn-send-back')?.addEventListener('click',      () => { Editor.canvas.getActiveObject()?.sendToBack();    Editor.canvas.renderAll(); pushHistory(); });
}

// ── Keyboard shortcuts ────────────────────────────────────────
function bindKeyboard() {
    document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

        const ctrl = e.ctrlKey || e.metaKey;

        if (ctrl && e.key === 'z') { e.preventDefault(); undo(); }
        if (ctrl && e.key === 'y') { e.preventDefault(); redo(); }
        if (ctrl && e.key === 'c') { e.preventDefault(); copySelected(); }
        if (ctrl && e.key === 'v') { e.preventDefault(); pasteClipboard(); }
        if (ctrl && e.key === 's') { e.preventDefault(); savePlan(); }
        if (ctrl && e.key === '+') { e.preventDefault(); zoomIn(); }
        if (ctrl && e.key === '-') { e.preventDefault(); zoomOut(); }
        if (ctrl && e.key === '0') { e.preventDefault(); zoomReset(); }
        if (ctrl && e.key === 'g') { e.preventDefault(); groupSelected(); }
        if (e.key === 'Delete' || e.key === 'Backspace') {
            if (Editor.canvas.getActiveObject()) { e.preventDefault(); deleteSelected(); }
        }
        if (e.key === 'Escape') { setTool('select'); }
    });
}

// ── Save ──────────────────────────────────────────────────────
function savePlan() {
    if (!Editor.planId) return;
    if (window.RSA21?.setAutoSaveStatus) window.RSA21.setAutoSaveStatus('saving');

    // Generate thumbnail (low-res PNG dataURL)
    const thumb = Editor.canvas.toDataURL({ format: 'jpeg', quality: 0.4, multiplier: 0.3 });

    const formData = new FormData();
    formData.append('canvas_data', JSON.stringify(Editor.canvas.toJSON(['data', 'id'])));
    formData.append('thumbnail',   thumb);
    formData.append('scale',       Editor.scale);
    formData.append('_token',      document.querySelector('meta[name="csrf-token"]')?.content || '');

    fetch(`/plans/${Editor.planId}/save`, { method: 'POST', body: formData })
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(() => {
            Editor.modified = false;
            if (window.RSA21?.setAutoSaveStatus) window.RSA21.setAutoSaveStatus('saved');
        })
        .catch(() => {
            if (window.RSA21?.setAutoSaveStatus) window.RSA21.setAutoSaveStatus('error');
        });
}

function startAutoSave() {
    setInterval(() => {
        if (Editor.modified) savePlan();
    }, 60000); // auto-save every 60s
}

// ── Export ────────────────────────────────────────────────────
function getExportCredits() {
    return (document.getElementById('exportCredits')?.value || '').trim();
}

function addCreditsToSvg(svg) {
    const credits = getExportCredits();
    if (!credits) return svg;
    const escaped = escHtml(credits);
    const creditsSvg = `<text x="10" y="100%" dy="-6" font-family="Helvetica, Arial, sans-serif" font-size="11" fill="#555" opacity="0.8">${escaped}</text>`;
    return svg.replace('</svg>', creditsSvg + '</svg>');
}

function exportSvg() {
    const svg = addCreditsToSvg(Editor.canvas.toSVG());
    const blob = new Blob([svg], { type: 'image/svg+xml' });
    downloadBlob(blob, 'plan.svg');
}

function exportPng() {
    const dpi    = parseFloat(document.getElementById('exportResolution')?.value || '300');
    const mult   = dpi / 96;
    const dataUrl = Editor.canvas.toDataURL({ format: 'png', multiplier: mult });
    const link    = document.createElement('a');
    link.download = 'plan.png';
    link.href     = dataUrl;
    link.click();
}

function exportPdf() {
    // For PDF: send canvas data to server to generate PDF
    const formData = new FormData();
    formData.append('svg',     addCreditsToSvg(Editor.canvas.toSVG()));
    formData.append('format',  document.getElementById('export-format')?.value || 'A4');
    formData.append('credits', getExportCredits());
    formData.append('_token',  document.querySelector('meta[name="csrf-token"]')?.content || '');

    fetch(`/plans/${Editor.planId}/export`, { method: 'POST', body: formData })
        .then(r => r.blob())
        .then(blob => downloadBlob(blob, 'plan.pdf'))
        .catch(() => { if (window.RSA21?.Flash) window.RSA21.Flash.show('danger', 'PDF-Export fehlgeschlagen.'); });
}

function downloadBlob(blob, filename) {
    const url  = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href     = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
}

// ── Status bar ────────────────────────────────────────────────
function updateStatusBar(x, y) {
    document.getElementById('status-x').textContent = 'X: ' + Math.round(x);
    document.getElementById('status-y').textContent = 'Y: ' + Math.round(y);
    document.getElementById('zoom-level').textContent = Math.round(Editor.canvas.getZoom() * 100) + '%';
    const footerZoom = document.getElementById('zoom-level-footer');
    if (footerZoom) footerZoom.textContent = Math.round(Editor.canvas.getZoom() * 100) + ' %';
}

// ── Rulers ────────────────────────────────────────────────────
function initRulers() {
    const rulerH = document.getElementById('ruler-h');
    const rulerV = document.getElementById('ruler-v');
    if (!rulerH || !rulerV) return;
    Editor.rulers.h = rulerH;
    Editor.rulers.v = rulerV;
    drawRuler(rulerH, 'h');
    drawRuler(rulerV, 'v');
}

function drawRuler(canvas, dir) {
    if (!canvas) return;
    const size   = dir === 'h' ? canvas.offsetWidth : canvas.offsetHeight;
    const ctx    = canvas.getContext ? canvas.getContext('2d') : null;
    if (!ctx) return;
    if (dir === 'h') canvas.width = size; else canvas.height = size;
    ctx.fillStyle   = '#888';
    ctx.font        = '9px sans-serif';
    ctx.strokeStyle = '#aaa';

    for (let i = 0; i < size; i += 50) {
        ctx.beginPath();
        if (dir === 'h') { ctx.moveTo(i, 14); ctx.lineTo(i, 20); ctx.fillText(i, i + 2, 12); }
        else             { ctx.moveTo(14, i); ctx.lineTo(20, i); ctx.fillText(i, 0, i - 2); }
        ctx.stroke();
    }
}

function updateRulers() { /* could redraw on zoom change */ }
function updateRulerCursor(x, y) { /* could show cursor line on rulers */ }

// ── Color helper ─────────────────────────────────────────────
function getActiveColor() {
    return document.getElementById('prop-stroke')?.value || '#333333';
}

// ── HTML escape (from app.js if available) ────────────────────
function escHtml(str) {
    if (window.RSA21?.escHtml) return window.RSA21.escHtml(str);
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

// ── Expose init ───────────────────────────────────────────────
window.initEditor = initEditor;
