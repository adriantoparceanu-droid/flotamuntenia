import './bootstrap';
import Sortable from 'sortablejs';

// Expunem global ca scripturile Blade sa-l poata folosi fara module imports
// (lista zilnica de comenzi initializeaza Sortable in <script> inline).
window.Sortable = Sortable;

// === TinyMCE self-host (Faza 6.2 — editor contracte) ====================
// Importam tot stack-ul TinyMCE ca module ES (Vite il bundleaza). Cu
// `skin: false` + `content_css: false` in init, TinyMCE nu mai face fetch
// dupa CSS-uri externe — folosim doar ce am bundlat aici.

import tinymce from 'tinymce/tinymce';

// Theme + model + icons
import 'tinymce/themes/silver';
import 'tinymce/models/dom';
import 'tinymce/icons/default';

// Plugin-uri necesare pentru editarea contractelor
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/table';
import 'tinymce/plugins/code';
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/wordcount';

// Skin + content CSS bundlate (in loc sa fie fetch-uite la runtime)
import 'tinymce/skins/ui/oxide/skin.min.css';
import 'tinymce/skins/ui/oxide/content.min.css';
import 'tinymce/skins/content/default/content.min.css';

window.tinymce = tinymce;

/**
 * Initializeaza un editor TinyMCE pe un element DOM.
 *
 * @param {HTMLElement|string} target — element sau selector CSS
 * @param {Object} options
 * @param {string} options.initialContent — HTML initial
 * @param {(html: string) => void} options.onChange — callback la modificari
 *        (apelat la blur + change; nu pe fiecare keystroke pentru perf)
 * @returns {Promise<tinymce.Editor>}
 */
window.initContractEditor = function (target, options = {}) {
    const config = {
        target: typeof target === 'string' ? document.querySelector(target) : target,
        // Evitam fetch-ul de skin-uri externe — folosim ce am bundlat
        skin: false,
        content_css: false,
        // UI minimal pentru contracte
        height: 600,
        menubar: 'edit insert format table',
        plugins: 'lists link table code autoresize wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link table | alignleft aligncenter alignright | code',
        branding: false,
        promotion: false,
        statusbar: true,
        elementpath: false,
        // Permite stiluri inline (necesare pentru contracte cu formatare bogata)
        valid_elements: '*[*]',
        extended_valid_elements: 'span[*],em[*],strong[*]',
        language: 'en', // TinyMCE ofera limited RO oficial; mentinem en pentru predictibilitate
        setup: (editor) => {
            editor.on('init', () => {
                if (options.initialContent !== undefined) {
                    editor.setContent(options.initialContent || '');
                }
            });
            editor.on('blur', () => {
                if (typeof options.onChange === 'function') {
                    options.onChange(editor.getContent());
                }
            });
            editor.on('change', () => {
                if (typeof options.onChange === 'function') {
                    options.onChange(editor.getContent());
                }
            });
        },
    };

    return tinymce.init(config);
};

/**
 * Distrugem un editor TinyMCE legat de un element (necesar la
 * navigare wire:navigate / re-render Livewire pentru a evita memory leaks
 * si initializari duble pe acelasi target).
 */
window.destroyContractEditor = function (target) {
    const el = typeof target === 'string' ? document.querySelector(target) : target;
    if (!el) return;
    const ed = tinymce.get(el.id);
    if (ed) ed.remove();
};

// === Chart.js (Dashboard analitic) ======================================
// Importam Chart.js cu auto-register (toate controllers + scales + elements
// + plugins inregistrate global, fara import individual).
import Chart from 'chart.js/auto';

window.Chart = Chart;

/**
 * Wrapper standardizat pentru chart Line — trend comenzi 30 zile.
 * Distruge instanta veche daca exista pe acelasi canvas (necesar la
 * livewire:navigating sau refresh manual).
 *
 * @param {HTMLCanvasElement|string} target
 * @param {{labels: string[], datasets: Array}} data
 * @returns {Chart}
 */
window.dashboardLineChart = function (target, data) {
    const canvas = typeof target === 'string' ? document.querySelector(target) : target;
    if (!canvas) return null;

    // Cleanup instanta veche pe acelasi canvas (livewire:navigating back)
    const existing = Chart.getChart(canvas);
    if (existing) existing.destroy();

    return new Chart(canvas, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { backgroundColor: 'rgba(17, 24, 39, 0.9)', padding: 8, titleFont: { size: 12 }, bodyFont: { size: 12 } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
                y: { beginAtZero: true, ticks: { font: { size: 10 }, precision: 0 } },
            },
        },
    });
};

/**
 * Wrapper standardizat pentru chart Donut — distributie tipuri comenzi luna.
 */
window.dashboardDonutChart = function (target, data) {
    const canvas = typeof target === 'string' ? document.querySelector(target) : target;
    if (!canvas) return null;

    const existing = Chart.getChart(canvas);
    if (existing) existing.destroy();

    return new Chart(canvas, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    padding: 8,
                    callbacks: {
                        label: (ctx) => {
                            const val = ctx.parsed;
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                            return `${ctx.label}: ${val} (${pct}%)`;
                        },
                    },
                },
            },
        },
    });
};

/**
 * Wrapper standardizat pentru chart Bar — cheltuieli vs vanzari 6 luni.
 * Suporta 2 datasets paralele (grouped bars).
 */
window.dashboardBarChart = function (target, data) {
    const canvas = typeof target === 'string' ? document.querySelector(target) : target;
    if (!canvas) return null;

    const existing = Chart.getChart(canvas);
    if (existing) existing.destroy();

    return new Chart(canvas, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    padding: 8,
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${new Intl.NumberFormat('ro-RO', { minimumFractionDigits: 2 }).format(ctx.parsed.y)} lei`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 10 },
                        callback: (val) => new Intl.NumberFormat('ro-RO').format(val),
                    },
                },
            },
        },
    });
};
