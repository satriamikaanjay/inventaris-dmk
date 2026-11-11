{{-- Responsif mobile bersama untuk semua halaman yang memakai sidebar (admin & master admin). --}}
@once
<style>
    .mobile-menu-btn,
    .mobile-sidebar-backdrop {
        display: none;
    }

    @media (max-width: 768px) {
        html, body {
            overflow-x: hidden;
            max-width: 100vw;
        }

        /* ===== Sidebar off-canvas + tombol menu ===== */
        sidebar {
            width: min(82vw, 290px) !important;
            transform: translateX(-100%);
            z-index: 1001 !important;
            box-shadow: none;
            -webkit-overflow-scrolling: touch;
        }

        sidebar.mobile-open {
            transform: translateX(0);
            box-shadow: 8px 0 30px rgba(15, 23, 42, 0.25);
        }

        .mobile-menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 10px;
            left: 12px;
            z-index: 1000;
            width: 44px;
            height: 44px;
            border: 1px solid rgba(0, 0, 0, 0.12);
            border-radius: 12px;
            background: #ffffff;
            color: var(--primary-color, #1a4f91);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .mobile-menu-btn svg {
            width: 24px;
            height: 24px;
        }

        .mobile-sidebar-backdrop {
            display: block;
            position: fixed;
            inset: 0;
            z-index: 1000;
            background: rgba(15, 23, 42, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }

        .mobile-sidebar-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        body.mobile-nav-open {
            overflow: hidden;
        }

        /* ===== Area konten ===== */
        main {
            margin-left: 0 !important;
            width: 100% !important;
            max-width: 100vw;
            min-width: 0;
            padding: 68px 14px 20px !important;
            gap: 18px !important;
        }

        main > *,
        main .card-box,
        main .card {
            min-width: 0;
            max-width: 100%;
        }

        main img,
        main canvas,
        main svg:not(.keep-size) {
            max-width: 100%;
        }

        /* ===== Header halaman ===== */
        .main-header {
            flex-direction: column;
            align-items: stretch !important;
            gap: 12px !important;
        }

        .main-header .header-title h1,
        .header-title h1 {
            font-size: 20px !important;
            line-height: 1.3;
        }

        .header-actions,
        .navbar-top-row,
        .navbar-search-row,
        .chip-actions,
        .table-toolbar,
        .aset-toolbar,
        .perm-toolbar,
        .akun-actions,
        .chart-filters,
        .modal-footer {
            flex-wrap: wrap !important;
            gap: 8px;
        }

        .header-actions > *,
        .table-toolbar > *,
        .aset-toolbar > * {
            max-width: 100%;
        }

        /* ===== Grid -> satu kolom ===== */
        .layout-grid,
        .dashboard-grid-row,
        .tables-grid-row,
        .dashboard-panels,
        .summary-grid,
        .stats-grid,
        .role-grid,
        .kop-editor-grid,
        .doc-meta-grid,
        .filter-grid,
        .kpi-grid,
        .action-buttons-grid,
        .summary-card,
        .info-row {
            grid-template-columns: 1fr !important;
        }

        .stats-grid,
        .kpi-grid,
        .asset-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) !important;
        }

        [style*="grid-template-columns"]:not([style*="auto-fit"]):not([style*="auto-fill"]) {
            grid-template-columns: 1fr !important;
        }

        [style*="auto-fill"][style*="320px"] {
            grid-template-columns: 1fr !important;
        }

        /* ===== Tabel: scroll horizontal ===== */
        table {
            display: block;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        th, td {
            white-space: nowrap;
        }

        td .chip-actions,
        td .action-buttons {
            flex-wrap: nowrap !important;
        }

        /* ===== Modal ===== */
        .modal-container,
        .preview-modal-dialog,
        .nav-loading-card {
            width: calc(100vw - 24px) !important;
            max-width: calc(100vw - 24px) !important;
            max-height: 92vh;
        }

        .modal-container,
        .preview-modal-dialog {
            display: flex;
            flex-direction: column;
        }

        .modal-body,
        .preview-modal-body {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            min-height: 0;
        }

        .modal-header,
        .modal-body,
        .modal-footer {
            padding-left: 16px !important;
            padding-right: 16px !important;
        }

        .modal-footer button,
        .modal-footer .btn {
            flex: 1 1 auto;
        }

        .preview-modal-topbar {
            flex-wrap: wrap;
            gap: 8px;
        }

        /* ===== Form: nyaman disentuh & tidak zoom di iOS ===== */
        input:not([type="checkbox"]):not([type="radio"]):not([type="range"]),
        select,
        textarea {
            font-size: 16px !important;
            max-width: 100%;
        }

        button,
        .btn,
        a.btn {
            min-height: 38px;
        }

        .filter-form,
        .filter-select,
        .navbar-filter-select {
            width: 100% !important;
            max-width: 100%;
        }

        .card-header,
        .card-body,
        .card-footer {
            padding-left: 14px;
            padding-right: 14px;
        }

        .pengajuan-content-card,
        .card-box {
            padding: 16px !important;
        }

        /* Toast / notifikasi melayang */
        [class*="toast"] {
            max-width: calc(100vw - 24px);
        }
    }

    @media (max-width: 480px) {
        main {
            padding: 66px 10px 16px !important;
        }

        .header-title h1 {
            font-size: 18px !important;
        }

        .stats-grid,
        .kpi-grid {
            grid-template-columns: 1fr 1fr !important;
        }
    }
</style>

<button type="button" class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Buka menu" aria-expanded="false">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
        <path d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>
<div class="mobile-sidebar-backdrop" id="mobile-sidebar-backdrop"></div>

<script>
    (function () {
        var btn = document.getElementById('mobile-menu-btn');
        var backdrop = document.getElementById('mobile-sidebar-backdrop');
        var sb = document.querySelector('sidebar');
        if (!btn || !backdrop || !sb) return;

        function setOpen(open) {
            sb.classList.toggle('mobile-open', open);
            backdrop.classList.toggle('show', open);
            document.body.classList.toggle('mobile-nav-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        btn.addEventListener('click', function () {
            setOpen(!sb.classList.contains('mobile-open'));
        });
        backdrop.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });
        sb.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]'); var h = a ? a.getAttribute('href') : ''; if (a && h && h.charAt(0) !== '#' && h.indexOf('javascript') !== 0 && window.innerWidth <= 768) setOpen(false);
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) setOpen(false);
        });
    })();
</script>
@endonce
