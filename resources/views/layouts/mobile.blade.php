@once
@php
    $__namaUserMobile = \Illuminate\Support\Facades\Auth::check() ? (\Illuminate\Support\Facades\Auth::user()->nama_lengkap ?? 'User') : 'User';
    $__roleRawMobile = \Illuminate\Support\Facades\Auth::check() ? strtolower(str_replace([' ', '-'], '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? 'admin')))) : 'admin';
    $__labelRoleMobile = ($__roleRawMobile === 'master_admin' || $__roleRawMobile === 'masteradmin') ? 'Master Admin' : 'Admin';
    $__inisialUserMobile = strtoupper(substr(trim((string) $__namaUserMobile), 0, 1) ?: 'U');
@endphp
<style>
    .mobile-topbar,
    .mobile-menu-btn,
    .mobile-sidebar-backdrop {
        display: none;
    }

    @media screen and (max-width: 768px) {
        html {
            font-size: 13px !important;
            -webkit-text-size-adjust: 100%;
        }

        html,
        html body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
            font-size: 12.5px !important;
            line-height: 1.4 !important;
        }

        html body .mobile-topbar {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 48px;
            padding: 0 12px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(15, 23, 42, 0.1);
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
            z-index: 1002;
        }

        html body .mobile-topbar-left {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
        }

        html body .mobile-menu-btn {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            position: static !important;
            width: 34px !important;
            height: 34px !important;
            min-height: 34px !important;
            padding: 0 !important;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background: #f8fafc;
            color: var(--primary-color, #1a4f91);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
            cursor: pointer;
            flex-shrink: 0;
            -webkit-tap-highlight-color: transparent;
        }

        html body .mobile-menu-btn:active {
            background: #e2e8f0;
            transform: scale(0.96);
        }

        html body .mobile-menu-btn svg {
            width: 18px !important;
            height: 18px !important;
        }

        html body .mobile-brand-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        html body .mobile-brand-logo {
            background: var(--primary-color, #1a4f91);
            color: #ffffff;
            font-weight: 800;
            font-size: 11px;
            width: 28px;
            height: 24px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            letter-spacing: 0.3px;
        }

        html body .mobile-brand-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.15;
        }

        html body .mobile-brand-title {
            font-size: 12.5px;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        html body .mobile-brand-sub {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
        }

        html body .mobile-topbar-right {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        html body .mobile-user-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 8px 3px 4px;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            max-width: 135px;
        }

        html body .mobile-user-avatar {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--primary-color, #1a4f91);
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        html body .mobile-user-name {
            font-size: 10.5px;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        html body sidebar {
            width: min(76vw, 248px) !important;
            transform: translateX(-105%) !important;
            z-index: 1005 !important;
            box-shadow: none !important;
            -webkit-overflow-scrolling: touch;
        }

        html body sidebar.mobile-open {
            transform: translateX(0) !important;
            box-shadow: 10px 0 30px rgba(15, 23, 42, 0.28) !important;
        }

        html body .sidebar-brand {
            padding: 13px 14px !important;
            gap: 10px !important;
        }

        html body .brand-logo {
            width: 36px !important;
            height: 30px !important;
            font-size: 16px !important;
            border-radius: 7px !important;
        }

        html body .brand-text .sistem {
            font-size: 14.5px !important;
        }

        html body .brand-text .master-admin {
            font-size: 11px !important;
        }

        html body .sidebar-menu {
            padding: 6px 12px !important;
        }

        html body .menu-section-title {
            font-size: 10px !important;
            margin: 10px 0 5px 0 !important;
            letter-spacing: 0.4px !important;
        }

        html body .menu-item {
            padding: 8px 10px !important;
            font-size: 12px !important;
            gap: 9px !important;
            border-radius: 7px !important;
            margin-bottom: 3px !important;
            min-height: 34px !important;
        }

        html body .menu-item svg {
            width: 17px !important;
            height: 17px !important;
        }

        html body .submenu-pengajuan {
            padding: 4px 0 4px 9px !important;
            margin: 3px 0 5px 14px !important;
            gap: 4px !important;
        }

        html body .submenu-pengajuan-btn {
            padding: 6px 9px !important;
            font-size: 11px !important;
            min-height: 30px !important;
        }

        html body .sidebar-user {
            padding: 10px 14px !important;
            gap: 9px !important;
        }

        html body .user-avatar {
            width: 32px !important;
            height: 32px !important;
            font-size: 13px !important;
        }

        html body .user-info .user-name {
            font-size: 12px !important;
        }

        html body .user-info .user-role {
            font-size: 10.5px !important;
        }

        html body .mobile-sidebar-backdrop {
            display: block !important;
            position: fixed;
            inset: 0;
            z-index: 1004;
            background: rgba(15, 23, 42, 0.48);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.22s ease, visibility 0.22s ease;
        }

        html body .mobile-sidebar-backdrop.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        html body.mobile-nav-open {
            overflow: hidden !important;
        }

        html body main {
            margin-left: 0 !important;
            width: 100% !important;
            max-width: 100vw !important;
            min-width: 0 !important;
            padding: 58px 10px 20px !important;
            gap: 10px !important;
        }

        html body main > *,
        html body main .card-box,
        html body main .card {
            min-width: 0 !important;
            max-width: 100% !important;
        }

        html body .main-header {
            position: relative !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            width: 100% !important;
            z-index: 5 !important;
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 8px !important;
            padding: 11px 12px !important;
            margin: 0 0 2px 0 !important;
            background: #ffffff !important;
            border: 1px solid rgba(15, 23, 42, 0.1) !important;
            border-radius: 11px !important;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04) !important;
        }

        html body .navbar-top-row {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 8px !important;
        }

        html body .navbar-search-row {
            padding-top: 8px !important;
        }

        html body .header-title {
            gap: 9px !important;
        }

        html body .header-icon-box {
            width: 34px !important;
            height: 34px !important;
            border-radius: 9px !important;
        }

        html body .header-icon-box svg {
            width: 18px !important;
            height: 18px !important;
        }

        html body .main-header .header-title h1,
        html body .header-title h1 {
            font-size: 15.5px !important;
            line-height: 1.25 !important;
            gap: 6px !important;
        }

        html body .main-header .header-title p,
        html body .header-title p {
            font-size: 11px !important;
            margin-top: 2px !important;
            line-height: 1.35 !important;
        }

        html body .header-actions {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 6px !important;
            width: 100% !important;
        }

        html body .header-actions > *,
        html body .header-actions form {
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }

        html body .header-actions form .btn,
        html body .header-actions form button {
            width: 100% !important;
        }

        html body .btn,
        html body button.btn,
        html body a.btn,
        html body .action-btn,
        html body .btn-qr {
            padding: 6px 10px !important;
            font-size: 11.5px !important;
            min-height: 32px !important;
            border-radius: 8px !important;
            gap: 5px !important;
        }

        html body .btn svg,
        html body button.btn svg,
        html body a.btn svg,
        html body .action-btn svg {
            width: 14px !important;
            height: 14px !important;
        }

        html body .btn-edit-sm,
        html body .btn-delete-sm,
        html body .btn-update-sm,
        html body .btn-cancel-sm,
        html body .btn-delete-role {
            padding: 4px 8px !important;
            font-size: 10.5px !important;
            min-height: 26px !important;
            border-radius: 6px !important;
        }

        html body .stats-grid,
        html body .summary-grid,
        html body .kpi-grid,
        html body .notif-ringkas,
        html body main > div[style*="minmax(180px"] {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            margin-top: 2px !important;
            margin-bottom: 4px !important;
        }

        html body .stat-card,
        html body .summary-card,
        html body .kpi-card,
        html body .notif-kartu,
        html body main > div[style*="minmax(180px"] > div {
            padding: 10px 11px !important;
            border-radius: 10px !important;
            gap: 4px !important;
        }

        html body .stat-card .stat-value,
        html body .summary-value,
        html body .kpi-card .kpi-value,
        html body .notif-kartu .angka,
        html body main > div[style*="minmax(180px"] > div > div:nth-child(2) {
            font-size: 17.5px !important;
            line-height: 1.15 !important;
        }

        html body .summary-label,
        html body .kpi-card .kpi-label,
        html body .notif-kartu .judul,
        html body main > div[style*="minmax(180px"] > div > div:first-child {
            font-size: 10.5px !important;
            margin-bottom: 3px !important;
        }

        html body .stat-card .stat-desc,
        html body .summary-note,
        html body .kpi-card .kpi-sub,
        html body .notif-kartu .ket,
        html body main > div[style*="minmax(180px"] > div > div:nth-child(3) {
            font-size: 9.8px !important;
            margin-top: 3px !important;
            line-height: 1.25 !important;
        }

        html body .summary-icon-badge {
            width: 28px !important;
            height: 28px !important;
            border-radius: 7px !important;
        }

        html body .summary-icon-badge svg {
            width: 15px !important;
            height: 15px !important;
        }

        html body .layout-grid,
        html body .dashboard-grid-row,
        html body .tables-grid-row,
        html body .dashboard-panels,
        html body .roles-layout,
        html body .role-grid,
        html body .kop-editor-grid,
        html body .doc-meta-grid,
        html body .filter-grid,
        html body .action-buttons-grid,
        html body .grid-2,
        html body .grid-3 {
            grid-template-columns: 1fr !important;
            gap: 10px !important;
        }

        html body .pengajuan-top-buttons {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 7px !important;
        }

        html body .btn-tab-pengajuan {
            padding: 9px 8px !important;
            font-size: 11px !important;
            border-radius: 9px !important;
            gap: 6px !important;
        }

        html body .card-box,
        html body .pengajuan-content-card,
        html body .step-box {
            padding: 12px !important;
            border-radius: 11px !important;
            margin-bottom: 6px !important;
        }

        html body .section-title,
        html body .card-box h2,
        html body .card-box h3 {
            font-size: 13.5px !important;
            margin-bottom: 10px !important;
            padding-bottom: 7px !important;
        }

        html body .form-group {
            gap: 4px !important;
            margin-bottom: 9px !important;
        }

        html body .form-group label {
            font-size: 11px !important;
        }

        html body input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="color"]),
        html body select,
        html body textarea,
        html body .filter-select,
        html body .navbar-filter-select {
            font-size: 12.5px !important;
            padding: 7px 10px !important;
            border-radius: 8px !important;
            max-width: 100%;
        }

        html body .aset-search-wrap {
            flex: 1 1 100% !important;
            max-width: 100% !important;
        }

        html body .aset-search-wrap input {
            padding: 7px 10px 7px 32px !important;
            font-size: 12px !important;
        }

        html body .aset-search-icon {
            left: 10px !important;
            width: 14px !important;
            height: 14px !important;
        }

        html body .search-box,
        html body .notif-filter .cari-wrap {
            min-width: 0 !important;
            width: 100% !important;
            flex: 1 1 100% !important;
            padding: 5px 10px !important;
            border-radius: 8px !important;
        }

        html body .search-box input,
        html body .notif-filter input[type="text"] {
            padding: 3px 4px !important;
            font-size: 12px !important;
        }

        html body .filter-form,
        html body .notif-filter,
        html body .aset-toolbar,
        html body .table-toolbar {
            gap: 6px !important;
        }

        html body .filter-form select,
        html body .notif-filter select {
            flex: 1 1 calc(50% - 4px) !important;
            min-width: 110px !important;
        }

        html body .scope-banner {
            padding: 9px 11px !important;
            font-size: 11px !important;
            border-radius: 9px !important;
            gap: 8px !important;
        }

        html body .rt-live-pill,
        html body .live-sync-badge {
            font-size: 10px !important;
            padding: 3px 8px !important;
        }

        html body .table-container,
        html body .table-wrap {
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            border-radius: 9px !important;
        }

        html body table:not(.kop-table):not(.doc-meta-table):not(.doc-sign-table) {
            max-width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        html body .card-box > table:not(.data-table),
        html body .card-box > .data-table {
            display: block;
            overflow-x: auto;
        }

        html body .table-container .data-table,
        html body .table-wrap .data-table {
            display: table !important;
        }

        html body .data-table th,
        html body table th {
            padding: 8px 9px !important;
            font-size: 10.5px !important;
            white-space: nowrap;
        }

        html body .data-table td,
        html body table td {
            padding: 8px 9px !important;
            font-size: 11.5px !important;
            white-space: nowrap;
        }

        html body .badge,
        html body .badge-status,
        html body .status-badge,
        html body .notif-badge {
            font-size: 10px !important;
            padding: 3px 7px !important;
        }

        html body .asset-grid {
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)) !important;
            gap: 10px !important;
            margin-top: 10px !important;
        }

        html body .asset-card {
            padding: 12px !important;
            border-radius: 11px !important;
        }

        html body .card-header {
            margin-bottom: 9px !important;
            padding-bottom: 8px !important;
        }

        html body .card-title {
            font-size: 13.5px !important;
        }

        html body .card-subtitle {
            font-size: 10.5px !important;
        }

        html body .card-body {
            gap: 6px !important;
            margin-bottom: 11px !important;
        }

        html body .info-row {
            display: flex !important;
            justify-content: space-between !important;
            font-size: 11.5px !important;
        }

        html body .chip-wrapper {
            padding: 9px 11px !important;
            border-radius: 9px !important;
        }

        html body .chip-name {
            font-size: 12px !important;
        }

        html body .chip-code {
            font-size: 10px !important;
            padding: 3px 6px !important;
        }

        html body .notif-item {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 8px !important;
            padding: 11px 12px !important;
            border-radius: 10px !important;
        }

        html body .notif-nama {
            font-size: 13px !important;
        }

        html body .notif-kode,
        html body .notif-meta {
            font-size: 11px !important;
            gap: 4px 12px !important;
        }

        html body .notif-aksi {
            align-self: flex-end !important;
        }

        html body .btn-lihat {
            padding: 6px 11px !important;
            font-size: 11px !important;
        }

        html body .reset-req-card {
            padding: 11px 12px !important;
            gap: 10px !important;
        }

        html body .reset-req-form-inline {
            width: 100% !important;
        }

        html body .reset-req-input-pw {
            min-width: 0 !important;
            width: 100% !important;
            flex: 1 1 100% !important;
        }

        html body .sidebar-form-panel {
            width: 100vw !important;
            max-width: 100vw !important;
        }

        html body .sidebar-header,
        html body .modal-header {
            padding: 12px 14px !important;
        }

        html body .sidebar-header h3,
        html body .modal-header h3 {
            font-size: 14.5px !important;
        }

        html body .sidebar-body,
        html body .modal-body {
            padding: 12px !important;
            gap: 12px !important;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        html body .sidebar-footer,
        html body .modal-footer {
            padding: 10px 14px !important;
            flex-wrap: wrap !important;
            gap: 6px !important;
        }

        html body .modal-container,
        html body .preview-modal-dialog,
        html body .qr-modal-box {
            width: calc(100vw - 16px) !important;
            max-width: calc(100vw - 16px) !important;
            max-height: 90vh !important;
            border-radius: 12px !important;
        }

        html body .preview-modal-header {
            padding: 11px 13px !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 8px !important;
        }

        html body .preview-modal-header h3 {
            font-size: 13.5px !important;
        }

        html body .preview-modal-body {
            padding: 10px 8px !important;
            overflow-x: auto !important;
        }

        html body .a4-paper-sheet {
            min-width: 680px !important;
            padding: 16px 18px !important;
            font-size: 11px !important;
        }

        html body .chart-bars-container {
            height: 155px !important;
            padding: 14px 6px 4px !important;
            gap: 6px !important;
            overflow-x: auto !important;
        }

        html body .bar-fill {
            width: 24px !important;
            border-radius: 5px 5px 0 0 !important;
        }

        html body .bar-val {
            font-size: 10px !important;
        }

        html body .bar-lbl {
            font-size: 9.5px !important;
            max-width: 54px !important;
        }

        html body .panel-head-title {
            font-size: 13px !important;
        }

        html body .list-item-row {
            padding: 9px 11px !important;
            border-radius: 9px !important;
            gap: 8px !important;
        }

        html body .list-icon-box {
            width: 30px !important;
            height: 30px !important;
            border-radius: 8px !important;
        }

        html body .role-card {
            padding: 11px 12px !important;
            border-radius: 10px !important;
            gap: 6px !important;
        }

        html body .role-header {
            flex-wrap: wrap !important;
            gap: 6px !important;
        }

        html body .role-name {
            font-size: 13px !important;
        }

        html body .role-desc {
            font-size: 11px !important;
        }

        html body .inline-edit-form {
            flex-wrap: wrap !important;
            gap: 5px !important;
        }

        html body .inline-edit-form input {
            min-width: 110px !important;
            flex: 1 1 110px !important;
        }

        html body .nav-loading-card {
            width: calc(100vw - 32px) !important;
            max-width: 320px !important;
            padding: 20px 18px !important;
            border-radius: 14px !important;
        }

        html body .nav-loading-title {
            font-size: 14px !important;
        }

        html body .nav-loading-subtitle {
            font-size: 11.5px !important;
        }

        html body .custom-pagination-wrap nav > div:last-child {
            justify-content: center !important;
            gap: 8px !important;
        }

        html body .custom-pagination-wrap nav p {
            font-size: 11px !important;
            width: 100%;
            text-align: center;
        }

        html body .custom-pagination-wrap nav span > a,
        html body .custom-pagination-wrap nav span > span {
            min-width: 28px !important;
            padding: 5px 8px !important;
            font-size: 11px !important;
        }

        html body [class*="toast"] {
            max-width: calc(100vw - 20px) !important;
            font-size: 11.5px !important;
            padding: 10px 14px !important;
        }
    }

    @media screen and (max-width: 480px) {
        html body main {
            padding: 54px 8px 16px !important;
            gap: 8px !important;
        }

        html body .main-header {
            padding: 10px 11px !important;
        }

        html body .main-header .header-title h1,
        html body .header-title h1 {
            font-size: 14.5px !important;
        }

        html body .stats-grid,
        html body .summary-grid,
        html body .kpi-grid,
        html body .notif-ringkas,
        html body main > div[style*="minmax(180px"] {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 6px !important;
        }

        html body .asset-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media print {
        .mobile-topbar,
        .mobile-menu-btn,
        .mobile-sidebar-backdrop {
            display: none !important;
        }
    }
</style>

<div class="mobile-topbar" id="mobile-topbar">
    <div class="mobile-topbar-left">
        <button type="button" class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Buka menu" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                <path d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <div class="mobile-brand-wrap">
            <div class="mobile-brand-logo">SM</div>
            <div class="mobile-brand-text">
                <span class="mobile-brand-title">Inventaris DMK</span>
                <span class="mobile-brand-sub">{{ $__labelRoleMobile }}</span>
            </div>
        </div>
    </div>
    <div class="mobile-topbar-right">
        <div class="mobile-user-chip">
            <span class="mobile-user-avatar">{{ $__inisialUserMobile }}</span>
            <span class="mobile-user-name">{{ $__namaUserMobile }}</span>
        </div>
    </div>
</div>
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
            var a = e.target.closest('a[href]');
            var h = a ? a.getAttribute('href') : '';
            if (a && h && h.charAt(0) !== '#' && h.indexOf('javascript') !== 0 && window.innerWidth <= 768) {
                setOpen(false);
            }
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) setOpen(false);
        });
    })();
</script>
@endonce
