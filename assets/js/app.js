/**
 * Sonka Bau & Sonnenimmobilien - Multi Administration – Main Application JavaScript
 * ES6, no build process required.
 * @license MIT
 */

'use strict';

// ── Dark/Light mode ─────────────────────────────────────────
const ThemeManager = (() => {
    const KEY = 'rsa21_theme';

    function current() {
        return localStorage.getItem(KEY) ||
               (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    }

    function apply(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem(KEY, theme);
        const icon = document.getElementById('theme-icon');
        if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
        }
    }

    function toggle() {
        apply(current() === 'dark' ? 'light' : 'dark');
    }

    function init() {
        apply(current());
        document.getElementById('theme-toggle')?.addEventListener('click', toggle);
    }

    return { init, current, apply, toggle };
})();

// ── Sidebar ──────────────────────────────────────────────────
const Sidebar = (() => {
    function init() {
        const toggle  = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('.app-sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        if (!toggle || !sidebar) return;

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay?.classList.toggle('show');
        });

        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    return { init };
})();

// ── Flash messages ───────────────────────────────────────────
const Flash = (() => {
    function show(type, message, duration = 5000) {
        const container = document.getElementById('flash-container');
        if (!container) return;

        const id   = 'flash-' + Date.now();
        const icon = { success: 'bi-check-circle', danger: 'bi-x-circle',
                       warning: 'bi-exclamation-triangle', info: 'bi-info-circle' }[type] || 'bi-info-circle';

        const el = document.createElement('div');
        el.id        = id;
        el.className = `alert alert-${type} alert-dismissible flash-item d-flex align-items-center shadow mb-2`;
        el.innerHTML = `
            <i class="bi ${icon} me-2 fs-5"></i>
            <span class="flex-grow-1">${escHtml(message)}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.appendChild(el);

        if (duration > 0) {
            setTimeout(() => {
                const alert = document.getElementById(id);
                if (alert) {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            }, duration);
        }
    }

    return { show };
})();

// ── AJAX helpers ─────────────────────────────────────────────
const Http = (() => {
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function post(url, data = {}, json = false) {
        const headers = { 'X-CSRF-Token': getCsrfToken() };
        let body;

        if (json) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(data);
        } else {
            const fd = new FormData();
            fd.append('_token', getCsrfToken());
            Object.entries(data).forEach(([k, v]) => fd.append(k, v));
            body = fd;
        }

        const res = await fetch(url, { method: 'POST', headers, body });
        return res.json();
    }

    async function get(url) {
        const res = await fetch(url, { headers: { 'X-CSRF-Token': getCsrfToken() } });
        return res.json();
    }

    return { post, get, getCsrfToken };
})();

// ── Notifications ─────────────────────────────────────────────
const Notifications = (() => {
    function init() {
        document.querySelectorAll('[data-mark-read]').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.dataset.markRead;
                await Http.post(`/notifications/read/${id}`);
                this.closest('[data-notif-item]')?.classList.add('opacity-50');
            });
        });

        document.getElementById('mark-all-read')?.addEventListener('click', async function () {
            await Http.post('/notifications/read-all');
            document.querySelectorAll('[data-notif-item]').forEach(el => el.classList.add('opacity-50'));
            const badge = document.querySelector('.notif-badge');
            if (badge) badge.remove();
            Flash.show('success', 'Alle Benachrichtigungen als gelesen markiert.');
        });
    }

    return { init };
})();

// ── Confirm dialogs ───────────────────────────────────────────
function initConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function (e) {
            const msg = this.dataset.confirm || 'Wirklich löschen?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });
}

// ── Auto-save indicator ───────────────────────────────────────
function initAutoSaveIndicator() {
    const indicator = document.getElementById('autosave-indicator');
    if (!indicator) return;

    window.setAutoSaveStatus = (status) => {
        const icons = { saving: 'bi-cloud-arrow-up', saved: 'bi-cloud-check', error: 'bi-cloud-slash' };
        const texts = { saving: 'Wird gespeichert…', saved: 'Gespeichert', error: 'Fehler beim Speichern' };
        indicator.innerHTML = `<i class="bi ${icons[status] || icons.saved} me-1"></i>${texts[status] || ''}`;
        indicator.className = status === 'error' ? 'text-danger small' : 'text-muted small';
    };
}

// ── File upload (dropzone) ────────────────────────────────────
function initDropzone(el) {
    if (!el) return;

    const input = el.querySelector('input[type="file"]');

    el.addEventListener('dragover', e => {
        e.preventDefault();
        el.classList.add('drag-over');
    });

    el.addEventListener('dragleave', () => el.classList.remove('drag-over'));

    el.addEventListener('drop', e => {
        e.preventDefault();
        el.classList.remove('drag-over');
        if (input && e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        }
    });

    el.addEventListener('click', () => input?.click());
}

// ── Material stock indicator ──────────────────────────────────
function initStockBars() {
    document.querySelectorAll('[data-stock]').forEach(el => {
        const stock    = parseFloat(el.dataset.stock || '0');
        const minStock = parseFloat(el.dataset.minStock || '0');
        const max      = Math.max(minStock * 3, stock * 1.2, 10);
        const pct      = Math.min((stock / max) * 100, 100);

        const fill = el.querySelector('.stock-bar-fill');
        if (fill) {
            fill.style.width = pct + '%';
            fill.style.background = stock <= 0 ? '#dc3545'
                : stock <= minStock ? '#ffc107'
                : '#198754';
        }
    });
}

// ── Symbol favourite toggle ───────────────────────────────────
function initFavouriteToggles() {
    document.querySelectorAll('[data-toggle-fav]').forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.stopPropagation();
            const id = this.dataset.toggleFav;
            const data = await Http.post(`/symbols/${id}/favourite`);
            if (data.favourite) {
                this.innerHTML = '<i class="bi bi-star-fill text-warning"></i>';
            } else {
                this.innerHTML = '<i class="bi bi-star text-muted"></i>';
            }
        });
    });
}

// ── Search filter (client-side) ───────────────────────────────
function initTableSearch() {
    document.querySelectorAll('[data-search-table]').forEach(input => {
        const tableId = input.dataset.searchTable;
        const table   = document.getElementById(tableId);
        if (!table) return;

        input.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    });
}

// ── GPS coordinates (link to OpenStreetMap) ───────────────────
function initGpsLinks() {
    document.querySelectorAll('[data-gps]').forEach(el => {
        const [lat, lng] = (el.dataset.gps || '').split(',').map(s => s.trim());
        if (lat && lng) {
            el.innerHTML = `<a href="https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=15/${lat}/${lng}"
                               target="_blank" rel="noopener" class="text-decoration-none">
                              <i class="bi bi-geo-alt me-1"></i>${lat}, ${lng}
                            </a>`;
        }
    });
}

// ── Tooltips ──────────────────────────────────────────────────
function initTooltips() {
    const els = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    els.forEach(el => new bootstrap.Tooltip(el));
}

// ── Helper ────────────────────────────────────────────────────
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

// ── Bootstrap form validation ─────────────────────────────────
function initFormValidation() {
    document.querySelectorAll('form.needs-validation').forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    });
}

// ── SMTP test button ──────────────────────────────────────────
function initSmtpTest() {
    document.getElementById('smtp-test-btn')?.addEventListener('click', async function () {
        this.disabled = true;
        this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Teste…';
        try {
            const data = await Http.get('/settings/smtp-test');
            Flash.show(data.success ? 'success' : 'danger', data.message || 'Unbekannter Fehler');
        } catch (e) {
            Flash.show('danger', 'Verbindungsfehler');
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-send me-1"></i>Test-E-Mail senden';
        }
    });
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
    Sidebar.init();
    Notifications.init();
    initConfirmForms();
    initAutoSaveIndicator();
    initTableSearch();
    initGpsLinks();
    initTooltips();
    initFormValidation();
    initSmtpTest();
    initStockBars();
    initFavouriteToggles();

    // Dropzones
    document.querySelectorAll('.dropzone').forEach(initDropzone);

    // Auto-dismiss flash messages already in the DOM
    document.querySelectorAll('.alert-dismissible[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            const a = bootstrap.Alert.getOrCreateInstance(el);
            a.close();
        }, parseInt(el.dataset.autoDismiss || '5000'));
    });
});

// Export for use in plan-editor.js
window.RSA21 = { ThemeManager, Flash, Http, escHtml };
