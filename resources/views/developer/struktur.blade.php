<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struktur Folder &amp; Anti-Deface — Developer Console</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        body { background: #0b1120; color: #e2e8f0; min-height: 100vh; }
        .topbar { background: #0f172a; border-bottom: 1px solid #1e293b; padding: 14px 26px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; position: sticky; top: 0; z-index: 40; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { width: 34px; height: 34px; border-radius: 8px; object-fit: contain; background: #fff; padding: 3px; }
        .brand h1 { font-size: 16.5px; font-weight: 800; color: #f8fafc; }
        .brand p { font-size: 11.5px; color: #94a3b8; }
        .top-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .btn-nav { padding: 7px 13px; border-radius: 9px; border: 1px solid #334155; background: #1e293b; color: #e2e8f0; text-decoration: none; font-size: 12px; font-weight: 700; }
        .btn-nav:hover { background: #334155; }
        .btn-logout { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; padding: 7px 14px; border-radius: 9px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn-logout:hover { background: #dc2626; color: #fff; }
        .container { max-width: 1480px; margin: 0 auto; padding: 20px 24px 48px; }
        .alert-ok { background: rgba(16, 185, 129, 0.14); border: 1px solid rgba(16, 185, 129, 0.4); color: #6ee7b7; padding: 11px 15px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; font-weight: 600; }
        .alert-warn { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.45); color: #fecaca; padding: 11px 15px; border-radius: 12px; margin-bottom: 12px; font-size: 13px; font-weight: 600; }
        .panel { background: #111827; border: 1px solid #1e293b; border-radius: 14px; padding: 16px 18px; margin-bottom: 18px; }
        .panel-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; }
        .panel-head h2 { font-size: 14.5px; font-weight: 800; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .scroll-box { max-height: 220px; overflow-y: auto; border: 1px solid #1e293b; border-radius: 10px; background: #0b1120; }
        .scroll-box::-webkit-scrollbar, .tree-scroll::-webkit-scrollbar, .code-scroll::-webkit-scrollbar { width: 7px; height: 7px; }
        .scroll-box::-webkit-scrollbar-thumb, .tree-scroll::-webkit-scrollbar-thumb, .code-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 999px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { position: sticky; top: 0; z-index: 2; text-align: left; padding: 8px 11px; background: #0f172a; color: #94a3b8; font-size: 10.5px; text-transform: uppercase; border-bottom: 1px solid #1e293b; }
        td { padding: 8px 11px; border-bottom: 1px solid #1e293b; color: #e2e8f0; vertical-align: middle; }
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .b-ok { background: rgba(16, 185, 129, 0.16); color: #34d399; }
        .b-warn { background: rgba(245, 158, 11, 0.16); color: #fbbf24; }
        .b-err { background: rgba(239, 68, 68, 0.18); color: #f87171; }
        .b-blue { background: rgba(56, 189, 248, 0.24); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.45); }
        .b-muted { background: rgba(100, 116, 139, 0.18); color: #94a3b8; }
        .btn-base { padding: 7px 14px; border-radius: 8px; border: 1px solid rgba(56, 189, 248, 0.4); background: rgba(56, 189, 248, 0.14); color: #7dd3fc; font-size: 11.5px; font-weight: 700; cursor: pointer; }
        .btn-base:hover { background: #0284c7; color: #fff; }
        .btn-view { display: inline-block; padding: 4px 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #93c5fd; font-size: 11px; font-weight: 700; cursor: pointer; }
        .btn-view:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

        .ide-shell { display: grid; grid-template-columns: 340px 1fr; border: 1px solid #1e293b; border-radius: 14px; overflow: hidden; background: #0d1322; min-height: 600px; }
        @media (max-width: 900px) { .ide-shell { grid-template-columns: 1fr; } }
        .ide-sidebar { background: #0f172a; border-right: 1px solid #1e293b; display: flex; flex-direction: column; }
        .ide-side-head { padding: 11px 14px; font-size: 11px; font-weight: 800; letter-spacing: 0.7px; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; }
        .tree-scroll { flex: 1; max-height: 580px; overflow-y: auto; overflow-x: auto; padding: 8px 6px; }
        .tree-row { display: flex; align-items: center; gap: 6px; padding: 5px 8px; border-radius: 6px; cursor: pointer; font-size: 12.5px; color: #cbd5e1; user-select: none; white-space: nowrap; }
        .tree-row:hover { background: rgba(56, 189, 248, 0.08); color: #f8fafc; }
        .tree-row.active { background: rgba(37, 99, 235, 0.25); color: #93c5fd; font-weight: 700; }
        .tree-row-new { background: rgba(56, 189, 248, 0.16); color: #38bdf8 !important; font-weight: 700; border-left: 3px solid #38bdf8; }
        .tree-row-new .tree-name { color: #38bdf8; }
        .tree-chevron { width: 14px; text-align: center; font-size: 11px; color: #94a3b8; flex-shrink: 0; }
        .tree-icon { font-size: 13px; flex-shrink: 0; }
        .tree-name { overflow: hidden; text-overflow: ellipsis; }
        .tree-children { padding-left: 16px; border-left: 1px solid #1e293b; margin-left: 13px; display: none; }
        .tree-children.open { display: block; }
        .anom-dot { width: 7px; height: 7px; border-radius: 50%; background: #ef4444; display: inline-block; margin-left: auto; flex-shrink: 0; }

        .ide-editor { display: flex; flex-direction: column; background: #020617; min-width: 0; }
        .ide-tabbar { background: #0f172a; border-bottom: 1px solid #1e293b; padding: 9px 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
        .ide-tab-title { font-family: Consolas, 'Courier New', monospace; font-size: 12.5px; color: #93c5fd; font-weight: 700; }
        .ide-meta { background: #0b1120; border-bottom: 1px solid #1e293b; padding: 7px 14px; display: flex; flex-wrap: wrap; gap: 14px; font-size: 11.5px; color: #94a3b8; }
        .code-scroll { flex: 1; max-height: 540px; overflow: auto; }
        .code-table { width: 100%; border-collapse: collapse; font-family: Consolas, 'Courier New', monospace; font-size: 12.5px; line-height: 1.55; }
        .code-table td { border: none; padding: 0 12px 0 0; vertical-align: top; }
        .code-ln { width: 54px; min-width: 54px; text-align: right; padding: 0 12px 0 8px !important; color: #475569; user-select: none; border-right: 1px solid #1e293b !important; background: #070c18; }
        .code-txt { padding-left: 14px !important; white-space: pre; color: #e2e8f0; tab-size: 4; }
        .code-row-changed td { background: rgba(56, 189, 248, 0.22); }
        .code-row-changed .code-txt { color: #bae6fd; }
        .code-row-changed .code-ln { background: rgba(14, 165, 233, 0.35); color: #38bdf8; font-weight: 800; border-left: 3px solid #38bdf8; }
        .diff-banner { background: rgba(56, 189, 248, 0.16); border-bottom: 1px solid rgba(56, 189, 248, 0.45); color: #7dd3fc; padding: 7px 14px; font-size: 12px; font-weight: 600; }
        .empty-editor { padding: 80px 24px; text-align: center; color: #64748b; font-size: 13px; }
        .btn-back-tree { display: none; padding: 4px 10px; border-radius: 6px; border: 1px solid #334155; background: #1e293b; color: #cbd5e1; font-size: 11px; font-weight: 700; cursor: pointer; }
        @media (max-width: 900px) {
            .ide-shell { grid-template-columns: 1fr; min-height: auto; }
            .ide-sidebar { border-right: none; border-bottom: 1px solid #1e293b; }
            .tree-scroll { max-height: 300px; }
            .code-scroll { max-height: 440px; }
            .btn-back-tree { display: inline-block; }
        }
        @media (max-width: 768px) {
            .container { padding: 12px 10px 32px; }
            .panel { padding: 12px 10px; border-radius: 11px; margin-bottom: 12px; }
            .panel-head { margin-bottom: 8px; gap: 6px; }
            .panel-head h2 { font-size: 12.5px; }
            .badge { font-size: 9.5px; padding: 2px 6px; }
            .btn-base { padding: 5px 10px; font-size: 10.5px; }
            .alert-ok, .alert-warn { padding: 9px 11px; font-size: 11.5px; }
            .tree-row { padding: 4px 6px; font-size: 11.5px; }
            .code-table { font-size: 11px; line-height: 1.45; }
            .code-ln { width: 42px; min-width: 42px; padding: 0 8px 0 6px !important; }
            .ide-tab-title { font-size: 11px; word-break: break-all; }
            .ide-meta { font-size: 10.5px; gap: 8px; padding: 6px 10px; }
            .scroll-box table, .scroll-box thead, .scroll-box tbody, .scroll-box tr, .scroll-box td { display: block; width: 100%; }
            .scroll-box thead { display: none; }
            .scroll-box tbody tr { padding: 8px 10px; border-bottom: 1px solid #1e293b; }
            .scroll-box tbody tr:last-child { border-bottom: none; }
            .scroll-box tbody td { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; padding: 3px 0; border-bottom: none; text-align: right; font-size: 11px; word-break: break-word; overflow-wrap: anywhere; }
            .scroll-box tbody td[data-label]::before { content: attr(data-label); font-size: 9.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; text-align: left; flex-shrink: 0; }
        }
    </style>
</head>
<body>
    @include('developer.partials.topbar', [
        'pageTitle'      => 'Struktur Folder & Anti-Deface',
        'pageSubtitle'   => 'Explorer dropdown & peninjau kode (Read-Only)',
        'activeNav'      => 'struktur',
        'refreshOnclick' => 'window.location.reload()',
        'showWebLock'    => true,
        'webLocked'      => !empty($webLocked),
    ])

    <div class="container">
        @if (!empty($webLocked))
            <div class="alert-warn" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; border-width:2px;">
                <span><strong>MODE MAINTENANCE DARURAT AKTIF:</strong> Seluruh akses web &amp; API sedang dikunci sejak {{ $webLockInfo['locked_at'] ?? '-' }}. Pengunjung melihat halaman <em>Web Sedang Maintenance</em>.</span>
            </div>
        @endif
        @if (session('success'))
            <div class="alert-ok">{{ session('success') }}</div>
        @endif

        <section class="panel" style="border-top: 3px solid {{ count($anomalies) > 0 ? '#ef4444' : '#10b981' }};">
            <div class="panel-head">
                <h2>
                    <span class="badge {{ count($anomalies) > 0 ? 'b-err' : 'b-ok' }}">{{ count($anomalies) > 0 ? count($anomalies) . ' TEMUAN' : 'AMAN' }}</span>
                    Deteksi File Baru &amp; Perubahan Tidak Sah (Anti-Deface)
                </h2>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span style="font-size:11.5px; color:#94a3b8;">Baseline: {{ $baselineCreated }} ({{ $baselineTotal }} file)</span>
                    <form method="POST" action="{{ route('developer.struktur.baseline') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn-base">Perbarui Baseline</button>
                    </form>
                </div>
            </div>

            @if (count($anomalies) > 0)
                <div class="alert-warn">
                    Terdeteksi {{ count($anomalies) }} file baru atau berubah dibandingkan baseline! Klik <strong>Lihat Isi</strong> untuk memeriksa, lalu hapus lewat cPanel/hosting jika mencurigakan.
                </div>
                <div class="scroll-box">
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Tipe</th>
                                <th>Path</th>
                                <th>Ukuran</th>
                                <th>Terakhir Diubah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($anomalies as $item)
                                @php
                                    $stClass = $item['status'] === 'BARU' ? 'b-blue' : ($item['status'] === 'DIUBAH' ? 'b-warn' : 'b-muted');
                                    $itemType = $item['type'] ?? 'FILE';
                                @endphp
                                <tr>
                                    <td data-label="Status"><span class="badge {{ $stClass }}">{{ $item['status'] }}</span></td>
                                    <td data-label="Tipe"><span class="badge b-muted">{{ $itemType }}</span></td>
                                    <td data-label="Path" style="font-family:monospace; color:{{ $item['status'] === 'BARU' ? '#38bdf8' : '#93c5fd' }}; font-weight:{{ $item['status'] === 'BARU' ? '700' : '400' }};">{{ $item['path'] }}</td>
                                    <td data-label="Ukuran">{{ $itemType === 'FOLDER' ? '-' : number_format($item['size']) . ' B' }}</td>
                                    <td data-label="Diubah" style="color:#94a3b8;">{{ $item['mtime'] }}</td>
                                    <td data-label="Aksi">
                                        @if ($itemType === 'FOLDER')
                                            <span style="color:#fca5a5; font-size:11px; font-weight:700;">Folder baru ditambahkan</span>
                                        @elseif ($item['status'] !== 'DIHAPUS')
                                            <button type="button" class="btn-view" data-file="{{ $item['path'] }}" onclick="openFileViewer(this.dataset.file)">Lihat Isi</button>
                                        @else
                                            <span style="color:#64748b; font-size:11px;">File hilang</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert-ok" style="margin-bottom:0;">
                    Semua file proyek sesuai dengan baseline ({{ $baselineTotal }} file). Tidak ada file baru atau perubahan tidak sah.
                </div>
            @endif
        </section>

        <section class="ide-shell">
            <aside class="ide-sidebar">
                <div class="ide-side-head">
                    <span>Explorer • INVENTARIS-DMK</span>
                    <span class="badge b-muted">Read-Only</span>
                </div>
                <div class="tree-scroll" id="explorer-root">
                    @foreach ($rootDirs as $d)
                        <div class="tree-node" data-dir="{{ $d['path'] }}">
                            <div class="tree-row {{ !empty($d['is_new']) ? 'tree-row-new' : '' }}" onclick="toggleFolder(this)">
                                <span class="tree-chevron">▸</span>
                                <span class="tree-icon">📁</span>
                                <span class="tree-name">{{ $d['name'] }}</span>
                                @if (!empty($d['is_new']))
                                    <span class="badge b-blue" style="margin-left:auto;">BARU</span>
                                @elseif (!empty($d['has_anomaly']))
                                    <span class="anom-dot" title="Ada file baru/diubah di dalam folder ini"></span>
                                @endif
                            </div>
                            <div class="tree-children" data-loaded="0"></div>
                        </div>
                    @endforeach

                    @foreach ($rootFiles as $f)
                        <div class="tree-row tree-file {{ ($f['anomaly'] ?? '') === 'BARU' ? 'tree-row-new' : '' }}" data-file="{{ $f['path'] }}" onclick="openFileViewer(this.dataset.file, this)">
                            <span class="tree-chevron"></span>
                            <span class="tree-icon">📄</span>
                            <span class="tree-name">{{ $f['name'] }}</span>
                            @if (!empty($f['anomaly']))
                                <span class="badge {{ $f['anomaly'] === 'BARU' ? 'b-blue' : 'b-warn' }}" style="margin-left:auto;">{{ $f['anomaly'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </aside>

            <main class="ide-editor" id="ide-editor-pane">
                <div class="ide-tabbar">
                    <span class="ide-tab-title" id="editor-file-path">Belum ada file dipilih</span>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="badge b-muted" id="editor-anomaly-badge" style="display:none;"></span>
                        <button type="button" class="btn-back-tree" onclick="document.getElementById('explorer-root').scrollIntoView({ behavior: 'smooth', block: 'start' })">↑ Ke Explorer</button>
                    </div>
                </div>
                <div class="ide-meta" id="editor-meta-bar" style="display:none;">
                    <span>Ukuran: <strong id="meta-size">-</strong></span>
                    <span>Diubah: <strong id="meta-mtime">-</strong></span>
                    <span>SHA1: <code id="meta-sha1" style="color:#cbd5e1;">-</code></span>
                </div>
                <div class="diff-banner" id="editor-diff-banner" style="display:none;"></div>
                <div class="code-scroll" id="editor-body">
                    <div class="empty-editor">
                        Pilih folder di sebelah kiri untuk membuka dropdown isinya, lalu klik file untuk melihat kodenya (mode baca saja).<br>
                        <span style="font-size:11.5px; color:#475569;">Penghapusan atau pengeditan file hanya dapat dilakukan melalui cPanel / file manager hosting.</span>
                    </div>
                </div>
            </main>
        </section>
    </div>

    <script>
        const treeEndpoint = @json(route('developer.struktur.tree'));
        const fileEndpoint = @json(route('developer.struktur.file'));

        function escHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        async function toggleFolder(rowEl) {
            const node = rowEl.closest('.tree-node');
            const dirPath = node ? (node.getAttribute('data-dir') || '') : '';
            const childrenBox = node.querySelector('.tree-children');
            const chevron = rowEl.querySelector('.tree-chevron');
            const isOpen = childrenBox.classList.contains('open');

            if (isOpen) {
                childrenBox.classList.remove('open');
                chevron.textContent = '▸';
                return;
            }

            childrenBox.classList.add('open');
            chevron.textContent = '▾';

            if (childrenBox.getAttribute('data-loaded') === '1') {
                return;
            }

            childrenBox.innerHTML = '<div style="padding:4px 10px; font-size:11.5px; color:#64748b;">Memuat...</div>';

            try {
                const res = await fetch(treeEndpoint + '?dir=' + encodeURIComponent(dirPath), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                if (!json || !json.success) {
                    childrenBox.innerHTML = '<div style="padding:4px 10px; font-size:11.5px; color:#f87171;">Gagal memuat folder.</div>';
                    return;
                }

                let html = '';
                (json.dirs || []).forEach(d => {
                    const dirCls = d.is_new ? 'tree-row tree-row-new' : 'tree-row';
                    const dirBadge = d.is_new
                        ? '<span class="badge b-blue" style="margin-left:auto;">BARU</span>'
                        : (d.has_anomaly ? '<span class="anom-dot" title="Ada file baru/diubah di dalam folder ini"></span>' : '');
                    html += '<div class="tree-node" data-dir="' + escHtml(d.path) + '">'
                        + '<div class="' + dirCls + '" onclick="toggleFolder(this)">'
                        + '<span class="tree-chevron">▸</span>'
                        + '<span class="tree-icon">📁</span>'
                        + '<span class="tree-name">' + escHtml(d.name) + '</span>'
                        + dirBadge
                        + '</div>'
                        + '<div class="tree-children" data-loaded="0"></div>'
                        + '</div>';
                });

                (json.files || []).forEach(f => {
                    const isNewFile = f.anomaly === 'BARU';
                    const fileCls = isNewFile ? 'tree-row tree-file tree-row-new' : 'tree-row tree-file';
                    const badge = f.anomaly
                        ? '<span class="badge ' + (isNewFile ? 'b-blue' : 'b-warn') + '" style="margin-left:auto;">' + escHtml(f.anomaly) + '</span>'
                        : '';
                    html += '<div class="' + fileCls + '" data-file="' + escHtml(f.path) + '" onclick="openFileViewer(this.dataset.file, this)">'
                        + '<span class="tree-chevron"></span>'
                        + '<span class="tree-icon">📄</span>'
                        + '<span class="tree-name">' + escHtml(f.name) + '</span>'
                        + badge
                        + '</div>';
                });

                if (html === '') {
                    html = '<div style="padding:4px 10px; font-size:11.5px; color:#475569; font-style:italic;">(Folder kosong)</div>';
                }

                childrenBox.innerHTML = html;
                childrenBox.setAttribute('data-loaded', '1');
            } catch (e) {
                childrenBox.innerHTML = '<div style="padding:4px 10px; font-size:11.5px; color:#f87171;">Terjadi kesalahan jaringan.</div>';
            }
        }

        async function openFileViewer(filePath, rowEl) {
            document.querySelectorAll('.tree-file.active').forEach(el => el.classList.remove('active'));
            if (rowEl) {
                rowEl.classList.add('active');
            } else {
                const match = document.querySelector('.tree-file[data-file="' + CSS.escape(filePath) + '"]');
                if (match) match.classList.add('active');
            }

            const titleEl = document.getElementById('editor-file-path');
            const anomEl = document.getElementById('editor-anomaly-badge');
            const metaBar = document.getElementById('editor-meta-bar');
            const diffBanner = document.getElementById('editor-diff-banner');
            const bodyEl = document.getElementById('editor-body');

            titleEl.textContent = filePath;
            if (diffBanner) diffBanner.style.display = 'none';
            bodyEl.innerHTML = '<div class="empty-editor">Memuat isi file...</div>';

            try {
                const res = await fetch(fileEndpoint + '?file=' + encodeURIComponent(filePath), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                if (!json || !json.success || !json.file) {
                    bodyEl.innerHTML = '<div class="empty-editor" style="color:#f87171;">Gagal memuat file.</div>';
                    return;
                }

                const f = json.file;
                document.getElementById('meta-size').textContent = Number(f.size || 0).toLocaleString('id-ID') + ' B';
                document.getElementById('meta-mtime').textContent = f.mtime || '-';
                document.getElementById('meta-sha1').textContent = f.sha1 || '-';
                metaBar.style.display = 'flex';

                if (f.anomaly) {
                    anomEl.textContent = f.anomaly;
                    anomEl.className = 'badge ' + (f.anomaly === 'BARU' ? 'b-blue' : 'b-warn');
                    anomEl.style.display = 'inline-flex';
                } else {
                    anomEl.style.display = 'none';
                }

                const changedList = Array.isArray(f.changed_lines) ? f.changed_lines : [];
                const changedSet = new Set(changedList);
                if (diffBanner) {
                    if (changedList.length > 0) {
                        const previewLines = changedList.slice(0, 15).join(', ') + (changedList.length > 15 ? ', ...' : '');
                        diffBanner.textContent = 'Terdeteksi ' + changedList.length + ' baris diedit/ditambah (ditandai warna biru terang): baris ' + previewLines;
                        diffBanner.style.display = 'block';
                    } else if (f.anomaly === 'BARU') {
                        diffBanner.textContent = 'File baru ditambahkan (ditandai biru terang, belum ada di baseline sebelumnya).';
                        diffBanner.style.display = 'block';
                    } else {
                        diffBanner.style.display = 'none';
                    }
                }

                if (f.notice) {
                    bodyEl.innerHTML = '<div style="padding:20px;"><div class="alert-warn">' + escHtml(f.notice) + '</div></div>';
                } else {
                    const lines = String(f.content ?? '').split(/\r?\n/);
                    const rows = lines.map((line, idx) => {
                        const ln = idx + 1;
                        const isChanged = changedSet.has(ln);
                        const rowCls = isChanged ? ' class="code-row-changed"' : '';
                        const lnLabel = isChanged ? ('~ ' + ln) : ln;
                        return '<tr' + rowCls + '><td class="code-ln">' + lnLabel + '</td><td class="code-txt">' + escHtml(line) + '</td></tr>';
                    }).join('');
                    bodyEl.innerHTML = '<table class="code-table"><tbody>' + rows + '</tbody></table>';
                }

                document.getElementById('ide-editor-pane').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } catch (e) {
                bodyEl.innerHTML = '<div class="empty-editor" style="color:#f87171;">Gagal menghubungi server.</div>';
            }
        }
    </script>
</body>
</html>
