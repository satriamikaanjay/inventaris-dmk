<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Developer Observability & Health Console — Inventaris DMK</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        body { background: #0b1120; color: #e2e8f0; min-height: 100vh; }
        .topbar { background: #0f172a; border-bottom: 1px solid #1e293b; padding: 14px 26px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; position: sticky; top: 0; z-index: 40; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { width: 34px; height: 34px; border-radius: 8px; object-fit: contain; background: #fff; padding: 3px; }
        .brand h1 { font-size: 16.5px; font-weight: 800; color: #f8fafc; }
        .brand p { font-size: 11.5px; color: #94a3b8; }
        .top-actions { display: flex; align-items: center; gap: 10px; }
        .live-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: rgba(16, 185, 129, 0.14); border: 1px solid rgba(16, 185, 129, 0.35); color: #34d399; font-size: 11.5px; font-weight: 700; }
        .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.35; } }
        .btn-logout { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; padding: 7px 14px; border-radius: 9px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn-logout:hover { background: #dc2626; color: #fff; }
        .container { max-width: 1420px; margin: 0 auto; padding: 22px 24px 48px; }
        .alert-ok { background: rgba(16, 185, 129, 0.14); border: 1px solid rgba(16, 185, 129, 0.4); color: #6ee7b7; padding: 12px 16px; border-radius: 12px; margin-bottom: 18px; font-size: 13px; font-weight: 600; }
        .alert-err { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 12px 16px; border-radius: 12px; margin-bottom: 18px; font-size: 13px; font-weight: 600; }
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr)); gap: 16px; margin-bottom: 20px; }
        .kpi-card { background: #111827; border: 1px solid #1e293b; border-radius: 14px; padding: 18px; }
        .kpi-label { font-size: 11.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; }
        .kpi-val { font-size: 24px; font-weight: 800; color: #f8fafc; margin: 6px 0 4px; }
        .kpi-danger { color: #f87171; }
        .kpi-safe { color: #34d399; }
        .kpi-sub { font-size: 12px; color: #94a3b8; word-break: break-word; }
        .dual-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 460px), 1fr)); gap: 18px; margin-bottom: 18px; }
        .panel { background: #111827; border: 1px solid #1e293b; border-radius: 14px; padding: 18px; margin-bottom: 18px; }
        .dual-grid .panel { margin-bottom: 0; }
        .panel-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; }
        .panel-head h2 { font-size: 14.5px; font-weight: 800; color: #f8fafc; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .scroll-box { max-height: 320px; overflow-y: auto; overflow-x: hidden; border: 1px solid #1e293b; border-radius: 10px; background: #0b1120; }
        .scroll-box::-webkit-scrollbar { width: 7px; height: 7px; }
        .scroll-box::-webkit-scrollbar-thumb { background: #334155; border-radius: 999px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { position: sticky; top: 0; z-index: 2; text-align: left; padding: 9px 11px; background: #0f172a; color: #94a3b8; font-size: 10.5px; text-transform: uppercase; border-bottom: 1px solid #1e293b; }
        td { padding: 9px 11px; border-bottom: 1px solid #1e293b; color: #e2e8f0; vertical-align: middle; word-break: break-word; overflow-wrap: anywhere; }
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 700; white-space: nowrap; }
        .b-web { background: rgba(56, 189, 248, 0.16); color: #38bdf8; }
        .b-mobile { background: rgba(168, 85, 247, 0.18); color: #c084fc; }
        .b-ok { background: rgba(16, 185, 129, 0.16); color: #34d399; }
        .b-warn { background: rgba(245, 158, 11, 0.16); color: #fbbf24; }
        .b-err { background: rgba(239, 68, 68, 0.18); color: #f87171; }
        .b-role { background: rgba(59, 130, 246, 0.18); color: #93c5fd; }
        .b-offline { background: rgba(100, 116, 139, 0.18); color: #94a3b8; }
        .dot-on { width: 7px; height: 7px; border-radius: 50%; background: #10b981; display: inline-block; }
        .dot-off { width: 7px; height: 7px; border-radius: 50%; background: #64748b; display: inline-block; }
        .btn-sim { padding: 6px 12px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.45); background: rgba(239, 68, 68, 0.15); color: #fca5a5; font-size: 11.5px; font-weight: 700; cursor: pointer; }
        .btn-sim:hover { background: #dc2626; color: #fff; }
        .btn-unblock { padding: 5px 11px; border-radius: 7px; border: 1px solid rgba(16, 185, 129, 0.45); background: rgba(16, 185, 129, 0.18); color: #6ee7b7; font-size: 11px; font-weight: 700; cursor: pointer; white-space: nowrap; }
        .btn-unblock:hover:not(:disabled) { background: #059669; color: #fff; border-color: #059669; }
        .btn-unblock:disabled { opacity: 0.55; cursor: not-allowed; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-top: 14px; }
        .fg label { display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px; }
        .fg input, .fg select { width: 100%; padding: 10px 12px; border-radius: 9px; border: 1px solid #334155; background: #020617; color: #f8fafc; font-size: 13px; }
        .btn-primary { margin-top: 16px; padding: 11px 20px; border: none; border-radius: 10px; background: linear-gradient(135deg, #0284c7, #2563eb); color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; }
        .lock-banner { padding: 14px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; }
        .lock-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.45); color: #fecaca; }
        .lock-safe { background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.35); color: #a7f3d0; }
        @media (max-width: 768px) {
            .container { padding: 12px 10px 32px; }
            .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 12px; }
            .kpi-card { padding: 11px; border-radius: 10px; }
            .kpi-label { font-size: 9.5px; }
            .kpi-val { font-size: 15.5px; margin: 4px 0 3px; }
            .kpi-sub { font-size: 10px; line-height: 1.35; }
            .dual-grid { gap: 12px; margin-bottom: 12px; }
            .panel { padding: 12px 10px; border-radius: 11px; margin-bottom: 12px; }
            .panel-head { margin-bottom: 8px; gap: 6px; }
            .panel-head h2 { font-size: 12.5px; gap: 6px; }
            .badge { font-size: 9.5px; padding: 2px 6px; }
            .scroll-box { max-height: 320px; }
            table, thead, tbody, tr, td { display: block; width: 100%; }
            thead { display: none; }
            tbody tr { padding: 8px 10px; border-bottom: 1px solid #1e293b; }
            tbody tr:last-child { border-bottom: none; }
            tbody td { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; padding: 3px 0; border-bottom: none; text-align: right; font-size: 11px; }
            tbody td[data-label]::before { content: attr(data-label); font-size: 9.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; text-align: left; flex-shrink: 0; }
            tbody td[colspan] { display: block; text-align: center !important; }
            .fg label { font-size: 11px; }
            .fg input, .fg select { padding: 8px 10px; font-size: 12px; }
            .btn-primary { width: 100%; padding: 10px 14px; font-size: 12px; }
            .lock-banner { padding: 10px 12px; font-size: 11.5px; }
        }
    </style>
</head>
<body>
    @include('developer.partials.topbar', [
        'pageTitle'      => 'Developer Health & Observability Console',
        'pageSubtitle'   => 'Web & Mobile APK Telemetry, Error Log, IP Terblokir & Reset Master Admin',
        'activeNav'      => 'dashboard',
        'refreshOnclick' => 'manualRefreshDeveloper(this)',
        'liveText'       => 'Live (2 dtk)',
    ])

    <div class="container">
        @if (session('success'))
            <div class="alert-ok">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert-err">{{ $errors->first() }}</div>
        @endif

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Kecepatan Web App</div>
                <div class="kpi-val" id="kpi-web-avg">{{ $webStats['avg_ms'] }} ms</div>
                <div class="kpi-sub" id="kpi-web-sub">{{ $webStats['count'] }} req • Max {{ $webStats['max_ms'] }} ms • {{ $webStats['errors'] }} error</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Kecepatan Mobile APK API</div>
                <div class="kpi-val" id="kpi-mob-avg">{{ $mobileStats['avg_ms'] }} ms</div>
                <div class="kpi-sub" id="kpi-mob-sub">{{ $mobileStats['count'] }} req • Max {{ $mobileStats['max_ms'] }} ms • {{ $mobileStats['errors'] }} error</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Koneksi API di Mobile</div>
                @php
                    $mobState = $mobileApiStatus['state'] ?? 'disconnected';
                    $mobColorClass = $mobState === 'active' ? 'kpi-safe' : ($mobState === 'idle' ? '' : 'kpi-danger');
                @endphp
                <div class="kpi-val {{ $mobColorClass }}" id="kpi-mob-conn" @if($mobState === 'idle') style="color:#fbbf24;" @endif>
                    {{ $mobileApiStatus['label'] ?? 'Belum Terhubung' }}
                </div>
                <div class="kpi-sub" id="kpi-mob-conn-sub">
                    @if (!empty($mobileApiStatus['connected']))
                        Aktif: {{ $mobileApiStatus['active_devices'] ?? 0 }} perangkat • {{ $mobileApiStatus['last_endpoint'] ?? '-' }} ({{ $mobileApiStatus['last_seen_at'] ?? '-' }})
                    @else
                        Belum ada request masuk dari Mobile APK ke /api
                    @endif
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Kesehatan Server &amp; Database</div>
                <div class="kpi-val" id="kpi-db-status">{{ $health['db_status'] }} ({{ $health['db_latency_ms'] }} ms)</div>
                <div class="kpi-sub" id="kpi-srv-sub">PHP {{ $health['php_version'] }} • RAM {{ $health['memory_mb'] }} MB • Disk Terpakai {{ $health['disk_used_pct'] }}%</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Status Login Master Admin</div>
                <div class="kpi-val {{ !empty($masterLockState['is_blocked']) ? 'kpi-danger' : 'kpi-safe' }}" id="kpi-master-lock">
                    {{ !empty($masterLockState['is_blocked']) ? 'TERBLOKIR (3/3)' : 'Normal / Aktif' }}
                </div>
                <div class="kpi-sub" id="kpi-master-sub">Percobaan gagal: {{ $masterLockState['failed_count'] ?? 0 }} / 3</div>
            </div>
        </div>

        <div class="dual-grid">
            <section class="panel" style="border-top: 3px solid #38bdf8;">
                <div class="panel-head">
                    <h2><span class="badge b-web">WEB</span> Traffic &amp; Kecepatan Mengirim Data Web</h2>
                    <span class="badge b-web" id="badge-count-web">{{ count($webTelemetry) }} Request</span>
                </div>
                <div class="scroll-box">
                    <table>
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Endpoint Web</th>
                                <th>Status</th>
                                <th>Kecepatan</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-web-telemetry"></tbody>
                    </table>
                </div>
            </section>

            <section class="panel" style="border-top: 3px solid #c084fc;">
                <div class="panel-head">
                    <h2><span class="badge b-mobile">MOBILE APK</span> Traffic &amp; Kecepatan API Mobile</h2>
                    <div style="display:flex; align-items:center; gap:8px;">
                        @php
                            $mobBadgeCls = $mobState === 'active' ? 'b-ok' : ($mobState === 'idle' ? 'b-warn' : 'b-err');
                        @endphp
                        <span class="badge {{ $mobBadgeCls }}" id="badge-mobile-conn">{{ $mobileApiStatus['label'] ?? 'Belum Terhubung' }}</span>
                        <span class="badge b-mobile" id="badge-count-mobile">{{ count($mobileTelemetry) }} Request</span>
                    </div>
                </div>
                <div class="scroll-box">
                    <table>
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Endpoint API Mobile</th>
                                <th>Status</th>
                                <th>Kecepatan</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-mobile-telemetry"></tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="panel" style="border-top: 3px solid #f87171;">
            <div class="panel-head">
                <h2>Kotak Error Sistem (Error Request 4xx/5xx &amp; Log Error Fungsi)</h2>
                <span class="badge b-err" id="badge-count-errors">{{ count($recentErrorReqs) + count($logErrors) }} Error</span>
            </div>
            <div class="scroll-box" style="max-height: 260px;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 120px;">Sumber</th>
                            <th style="width: 110px;">Status / Level</th>
                            <th>Detail Error / Endpoint Bermasalah</th>
                            <th style="width: 155px;">Waktu</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-errors-combined"></tbody>
                </table>
            </div>
        </section>

        <section class="panel" style="border-top: 3px solid #ef4444;">
            <div class="panel-head">
                <h2>Daftar IP Diblokir &amp; Aktivitas Mencurigakan (WAF)</h2>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span class="badge b-ok" id="badge-count-blocked-ips">0 Terblokir</span>
                    <button type="button" id="btn-unblock-all-ips" class="btn-unblock" style="display:none;" onclick="bukaSemuaBlokirIp(this)">Buka Semua Blokir</button>
                </div>
            </div>
            <div id="waf-unblock-feedback" style="display:none; margin-bottom:10px;" class="alert-ok"></div>
            <div class="scroll-box" style="max-height: 290px;">
                <table>
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Nama Device</th>
                            <th>Aktivitas Mencurigakan / Penyebab</th>
                            <th>Waktu &amp; Durasi</th>
                            <th>Status</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-blocked-ips"></tbody>
                </table>
            </div>
        </section>

        <section class="panel" style="border-top: 3px solid #f59e0b;">
            <div class="panel-head">
                <h2>Permintaan Reset Password Master Admin (Salah / Lupa Sandi)</h2>
                <span class="badge b-warn" id="badge-count-master-req">{{ collect($masterResetRequests ?? [])->where('status', 'Menunggu')->count() }} Menunggu</span>
            </div>
            <div class="scroll-box" style="max-height: 230px; margin-bottom: 18px;">
                <table>
                    <thead>
                        <tr>
                            <th>Akun / Email Diminta</th>
                            <th>Catatan Master Admin</th>
                            <th>IP</th>
                            <th>Waktu Kirim</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-master-reset-reqs"></tbody>
                </table>
            </div>

            <div class="panel-head">
                <h2>Pemulihan &amp; Reset Kata Sandi Master Admin (Blokir 3x Salah)</h2>
            </div>

            <div id="master-lock-banner" class="lock-banner {{ !empty($masterLockState['is_blocked']) ? 'lock-danger' : 'lock-safe' }}">
                @if (!empty($masterLockState['is_blocked']))
                    <strong>PERINGATAN:</strong> Akun Master Admin sedang <strong>TERBLOKIR</strong> karena salah data login 3 kali berturut-turut (terakhir: {{ $masterLockState['last_email'] ?: '-' }} pada {{ $masterLockState['blocked_at'] ?: '-' }}). Gunakan form di bawah untuk mereset kata sandi &amp; membuka blokir.
                @else
                    Status login Master Admin saat ini <strong>Normal</strong> (percobaan gagal: {{ $masterLockState['failed_count'] ?? 0 }} / 3). Anda tetap dapat melakukan reset darurat jika Master Admin lupa kata sandi.
                @endif
            </div>

            <div id="master-reset-feedback" style="display:none; margin-bottom:14px;" class="alert-ok"></div>

            <form id="form-reset-master-pw" method="POST" action="{{ route('developer.reset_master_password') }}" onsubmit="return submitResetMasterPassword(event, this)">
                @csrf
                <div class="form-grid">
                    <div class="fg">
                        <label>Pilih Akun Master Admin (Username)</label>
                        <select name="master_user_id" required>
                            @forelse ($masterAdmins as $ma)
                                <option value="{{ $ma['id'] }}">{{ $ma['username'] }}</option>
                            @empty
                                <option value="">Tidak ada akun Master Admin ditemukan</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="fg">
                        <label>Kata Sandi Baru Master Admin</label>
                        <input type="password" name="new_password" placeholder="Minimal 6 karakter" required minlength="6">
                    </div>
                    <div class="fg">
                        <label>Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="new_password_confirmation" placeholder="Ulangi kata sandi baru" required minlength="6">
                    </div>
                </div>
                <button type="submit" id="btn-submit-reset-master" class="btn-primary">Reset Kata Sandi &amp; Buka Blokir Master Admin</button>
            </form>
        </section>
    </div>

    <input type="hidden" id="init-web-telemetry" value="{{ json_encode($webTelemetry) }}">
    <input type="hidden" id="init-mobile-telemetry" value="{{ json_encode($mobileTelemetry) }}">
    <input type="hidden" id="init-error-reqs" value="{{ json_encode($recentErrorReqs) }}">
    <input type="hidden" id="init-errors" value="{{ json_encode($logErrors) }}">
    <input type="hidden" id="init-master-reqs" value="{{ json_encode($masterResetRequests ?? []) }}">
    <input type="hidden" id="init-blocked-ips" value="{{ json_encode($blockedIps ?? []) }}">

    <script>
        let webTelemetryList = JSON.parse(document.getElementById('init-web-telemetry').value || '[]');
        let mobileTelemetryList = JSON.parse(document.getElementById('init-mobile-telemetry').value || '[]');
        let errorReqList = JSON.parse(document.getElementById('init-error-reqs').value || '[]');
        let errorLogList = JSON.parse(document.getElementById('init-errors').value || '[]');
        let masterResetReqList = JSON.parse(document.getElementById('init-master-reqs').value || '[]');
        let blockedIpList = JSON.parse(document.getElementById('init-blocked-ips').value || '[]');

        function esc(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function renderBoxTelemetry(tbodyId, badgeId, list, emptyMsg) {
            const tbody = document.getElementById(tbodyId);
            const badge = document.getElementById(badgeId);
            if (badge) badge.textContent = list.length + ' Request';
            if (!tbody) return;

            if (list.length === 0) {
                tbody.innerHTML = '\x3ctr\x3e\x3ctd colspan="5" style="text-align:center; color:#64748b; padding:22px;"\x3e' + esc(emptyMsg) + '\x3c/td\x3e\x3c/tr\x3e';
                return;
            }

            tbody.innerHTML = list.map(item => {
                const stBadge = item.status >= 400
                    ? '\x3cspan class="badge b-err"\x3e' + esc(item.status) + '\x3c/span\x3e'
                    : '\x3cspan class="badge b-ok"\x3e' + esc(item.status) + '\x3c/span\x3e';
                const ms = Number(item.duration_ms || 0);
                const msClass = ms > 400 ? 'b-err' : (ms > 120 ? 'b-warn' : 'b-ok');
                const timeShort = String(item.timestamp || '').slice(11) || item.timestamp;

                return '\x3ctr\x3e'
                    + '\x3ctd data-label="Method" style="font-weight:700;"\x3e' + esc(item.method) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Endpoint" style="font-family:monospace; color:#93c5fd;"\x3e' + esc(item.path) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Status"\x3e' + stBadge + '\x3c/td\x3e'
                    + '\x3ctd data-label="Kecepatan"\x3e\x3cspan class="badge ' + msClass + '"\x3e' + ms.toFixed(2) + ' ms\x3c/span\x3e\x3c/td\x3e'
                    + '\x3ctd data-label="Waktu" style="color:#94a3b8;"\x3e' + esc(timeShort) + '\x3c/td\x3e'
                    + '\x3c/tr\x3e';
            }).join('');
        }

        function renderKotakError() {
            const tbody = document.getElementById('tbody-errors-combined');
            const badge = document.getElementById('badge-count-errors');
            const combined = [];

            errorReqList.forEach(r => {
                combined.push({
                    source: r.channel === 'mobile' ? 'MOBILE API' : 'WEB HTTP',
                    sourceClass: r.channel === 'mobile' ? 'b-mobile' : 'b-web',
                    level: 'HTTP ' + r.status,
                    levelClass: 'b-err',
                    detail: r.method + ' ' + r.path + ' (' + Number(r.duration_ms || 0).toFixed(1) + ' ms)',
                    time: r.timestamp || '-'
                });
            });

            errorLogList.forEach(e => {
                combined.push({
                    source: 'LARAVEL LOG',
                    sourceClass: 'b-role',
                    level: e.level || 'ERROR',
                    levelClass: e.level === 'WARNING' ? 'b-warn' : 'b-err',
                    detail: e.message || '-',
                    time: e.time || '-'
                });
            });

            if (badge) badge.textContent = combined.length + ' Error';
            if (!tbody) return;

            if (combined.length === 0) {
                tbody.innerHTML = '\x3ctr\x3e\x3ctd colspan="4" style="text-align:center; color:#34d399; padding:22px;"\x3eSistem sehat — belum ada error request maupun error fungsi.\x3c/td\x3e\x3c/tr\x3e';
                return;
            }

            tbody.innerHTML = combined.map(c => {
                return '\x3ctr\x3e'
                    + '\x3ctd data-label="Sumber"\x3e\x3cspan class="badge ' + c.sourceClass + '"\x3e' + esc(c.source) + '\x3c/span\x3e\x3c/td\x3e'
                    + '\x3ctd data-label="Level"\x3e\x3cspan class="badge ' + c.levelClass + '"\x3e' + esc(c.level) + '\x3c/span\x3e\x3c/td\x3e'
                    + '\x3ctd data-label="Detail" style="font-family:monospace; font-size:11.5px;"\x3e' + esc(c.detail) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Waktu" style="color:#94a3b8;"\x3e' + esc(c.time) + '\x3c/td\x3e'
                    + '\x3c/tr\x3e';
            }).join('');
        }

        function fokusFormResetMaster() {
            const inputPw = document.querySelector('input[name="new_password"]');
            if (inputPw) {
                inputPw.scrollIntoView({ behavior: 'smooth', block: 'center' });
                inputPw.focus();
            }
        }

        function renderPermintaanResetMaster() {
            const tbody = document.getElementById('tbody-master-reset-reqs');
            const badge = document.getElementById('badge-count-master-req');
            const pendingCount = masterResetReqList.filter(x => x.status === 'Menunggu').length;
            if (badge) {
                badge.textContent = pendingCount + ' Menunggu';
                badge.className = 'badge ' + (pendingCount > 0 ? 'b-err' : 'b-ok');
            }
            if (!tbody) return;

            if (masterResetReqList.length === 0) {
                tbody.innerHTML = '\x3ctr\x3e\x3ctd colspan="6" style="text-align:center; color:#64748b; padding:20px;"\x3eBelum ada permintaan reset password dari Master Admin.\x3c/td\x3e\x3c/tr\x3e';
                return;
            }

            tbody.innerHTML = masterResetReqList.map(req => {
                const isWait = req.status === 'Menunggu';
                const stBadge = isWait
                    ? '\x3cspan class="badge b-err"\x3eMenunggu\x3c/span\x3e'
                    : '\x3cspan class="badge b-ok"\x3e' + esc(req.status) + '\x3c/span\x3e';
                const aksiBtn = isWait
                    ? '\x3cbutton type="button" onclick="fokusFormResetMaster()" style="padding:4px 10px; border-radius:6px; border:none; background:#0284c7; color:#fff; font-size:11px; font-weight:700; cursor:pointer;"\x3eProses Reset\x3c/button\x3e'
                    : '\x3cspan style="color:#64748b; font-size:11px;"\x3eSelesai\x3c/span\x3e';

                return '\x3ctr\x3e'
                    + '\x3ctd data-label="Akun" style="font-weight:700; color:#f8fafc;"\x3e' + esc(req.email_or_username) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Catatan"\x3e' + esc(req.catatan) + '\x3c/td\x3e'
                    + '\x3ctd data-label="IP" style="color:#94a3b8;"\x3e' + esc(req.ip) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Waktu" style="color:#94a3b8;"\x3e' + esc(req.created_at) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Status"\x3e' + stBadge + '\x3c/td\x3e'
                    + '\x3ctd data-label="Aksi"\x3e' + aksiBtn + '\x3c/td\x3e'
                    + '\x3c/tr\x3e';
            }).join('');
        }

        function renderTabelBlockedIps() {
            const tbody = document.getElementById('tbody-blocked-ips');
            const badge = document.getElementById('badge-count-blocked-ips');
            const btnAll = document.getElementById('btn-unblock-all-ips');

            const activeList = blockedIpList.filter(x => x.status === 'blocked' || x.status === 'warning');
            const blockedCount = blockedIpList.filter(x => x.status === 'blocked').length;

            if (badge) {
                badge.textContent = blockedCount > 0 ? (blockedCount + ' Terblokir') : '0 Terblokir';
                badge.className = 'badge ' + (blockedCount > 0 ? 'b-err' : 'b-ok');
            }
            if (btnAll) {
                btnAll.style.display = activeList.length > 0 ? 'inline-block' : 'none';
            }
            if (!tbody) return;

            if (blockedIpList.length === 0) {
                tbody.innerHTML = '\x3ctr\x3e\x3ctd colspan="6" style="text-align:center; color:#34d399; padding:22px;"\x3eAman — belum ada IP yang diblokir atau melakukan aktivitas mencurigakan.\x3c/td\x3e\x3c/tr\x3e';
                return;
            }

            tbody.innerHTML = blockedIpList.map(item => {
                const st = item.status || 'expired';
                let stBadge = '\x3cspan class="badge b-offline"\x3eKedaluwarsa\x3c/span\x3e';
                let waktuKet = esc(item.blocked_at || '-');
                let aksiHtml = '\x3cspan style="color:#64748b; font-size:11px;"\x3e-\x3c/span\x3e';

                if (st === 'blocked') {
                    stBadge = '\x3cspan class="badge b-err"\x3eTERBLOKIR\x3c/span\x3e';
                    const remMin = Math.max(1, Math.ceil(Number(item.remaining_sec || 0) / 60));
                    waktuKet = esc(item.blocked_at || '-') + '\x3cdiv style="font-size:10.5px; color:#fca5a5;"\x3eSisa ~' + remMin + ' mnt\x3c/div\x3e';
                    aksiHtml = '\x3cbutton type="button" class="btn-unblock" onclick="bukaBlokirIp(\'' + esc(item.ip) + '\', this)"\x3eBuka Blokir\x3c/button\x3e';
                } else if (st === 'warning') {
                    stBadge = '\x3cspan class="badge b-warn"\x3ePERINGATAN (' + esc(item.strikes || 1) + '/3)\x3c/span\x3e';
                    aksiHtml = '\x3cbutton type="button" class="btn-unblock" onclick="bukaBlokirIp(\'' + esc(item.ip) + '\', this)"\x3eBuka Blokir / Reset\x3c/button\x3e';
                } else if (st === 'unblocked') {
                    stBadge = '\x3cspan class="badge b-ok"\x3eDibuka\x3c/span\x3e';
                    waktuKet = esc(item.unblocked_at || item.blocked_at || '-');
                    aksiHtml = '\x3cspan style="color:#34d399; font-size:11px;"\x3eDibuka (' + esc(item.unblocked_by || 'Dev') + ')\x3c/span\x3e';
                }

                const uaShort = item.user_agent && item.user_agent !== '-'
                    ? '\x3cdiv style="font-size:10px; color:#64748b; max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="' + esc(item.user_agent) + '"\x3e' + esc(item.user_agent) + '\x3c/div\x3e'
                    : '';

                const pathKet = item.path && item.path !== '-'
                    ? '\x3cdiv style="font-size:10.5px; color:#93c5fd; font-family:monospace;"\x3e' + esc(item.path) + '\x3c/div\x3e'
                    : '';

                return '\x3ctr\x3e'
                    + '\x3ctd data-label="IP Address" style="font-family:monospace; font-weight:700; color:#f8fafc;"\x3e' + esc(item.ip) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Nama Device"\x3e\x3cdiv style="font-weight:600; color:#e2e8f0;"\x3e' + esc(item.device_name || 'Perangkat Tidak Dikenal') + '\x3c/div\x3e' + uaShort + '\x3c/td\x3e'
                    + '\x3ctd data-label="Aktivitas / Alasan"\x3e\x3cdiv\x3e' + esc(item.reason || '-') + '\x3c/div\x3e' + pathKet + '\x3c/td\x3e'
                    + '\x3ctd data-label="Waktu"\x3e' + waktuKet + '\x3c/td\x3e'
                    + '\x3ctd data-label="Status"\x3e' + stBadge + '\x3c/td\x3e'
                    + '\x3ctd data-label="Aksi" style="text-align:right;"\x3e' + aksiHtml + '\x3c/td\x3e'
                    + '\x3c/tr\x3e';
            }).join('');
        }

        function tampilkanFeedbackUnblock(pesan, isErr = false) {
            const box = document.getElementById('waf-unblock-feedback');
            if (!box) return;
            box.className = isErr ? 'alert-err' : 'alert-ok';
            box.textContent = pesan;
            box.style.display = 'block';
            setTimeout(() => { box.style.display = 'none'; }, 5000);
        }

        async function bukaBlokirIp(ip, btn) {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Membuka...';
            }
            try {
                const res = await fetch('{{ route("developer.unblock_ip") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ ip: ip })
                });
                const json = await res.json();
                if (!res.ok || !json.success) {
                    tampilkanFeedbackUnblock(json?.message || 'Gagal membuka blokir IP.', true);
                    if (btn) { btn.disabled = false; btn.textContent = 'Buka Blokir'; }
                    return;
                }
                if (Array.isArray(json.blockedIps)) {
                    blockedIpList = json.blockedIps;
                    renderTabelBlockedIps();
                }
                tampilkanFeedbackUnblock(json.message || ('Blokir IP ' + ip + ' berhasil dibuka.'));
            } catch (e) {
                tampilkanFeedbackUnblock('Terjadi kesalahan koneksi saat membuka blokir.', true);
                if (btn) { btn.disabled = false; btn.textContent = 'Buka Blokir'; }
            }
        }

        async function bukaSemuaBlokirIp(btn) {
            if (!confirm('Buka seluruh blokir IP WAF yang sedang aktif?')) return;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Membuka...';
            }
            try {
                const res = await fetch('{{ route("developer.unblock_all_ips") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({})
                });
                const json = await res.json();
                if (res.ok && json.success) {
                    if (Array.isArray(json.blockedIps)) {
                        blockedIpList = json.blockedIps;
                        renderTabelBlockedIps();
                    }
                    tampilkanFeedbackUnblock(json.message || 'Semua blokir IP berhasil dibuka.');
                } else {
                    tampilkanFeedbackUnblock(json?.message || 'Gagal membuka semua blokir.', true);
                }
            } catch (e) {
                tampilkanFeedbackUnblock('Terjadi kesalahan koneksi.', true);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Buka Semua Blokir';
                }
            }
        }

        let isPollingDev = false;

        function terapkanSnapshotDashboard(d) {
            if (!d) return;
            webTelemetryList = d.webTelemetry || [];
            mobileTelemetryList = d.mobileTelemetry || [];
            errorReqList = d.recentErrorReqs || [];
            errorLogList = d.logErrors || [];
            masterResetReqList = d.masterResetRequests || [];
            blockedIpList = d.blockedIps || [];

            document.getElementById('kpi-web-avg').textContent = (d.webStats?.avg_ms ?? 0) + ' ms';
            document.getElementById('kpi-web-sub').textContent = (d.webStats?.count ?? 0) + ' req • Max ' + (d.webStats?.max_ms ?? 0) + ' ms • ' + (d.webStats?.errors ?? 0) + ' error';
            document.getElementById('kpi-mob-avg').textContent = (d.mobileStats?.avg_ms ?? 0) + ' ms';
            document.getElementById('kpi-mob-sub').textContent = (d.mobileStats?.count ?? 0) + ' req • Max ' + (d.mobileStats?.max_ms ?? 0) + ' ms • ' + (d.mobileStats?.errors ?? 0) + ' error';

            const mobStatus = d.mobileApiStatus || {};
            const mobState = mobStatus.state || 'disconnected';
            const mobConnEl = document.getElementById('kpi-mob-conn');
            const mobConnSubEl = document.getElementById('kpi-mob-conn-sub');
            const mobConnBadgeEl = document.getElementById('badge-mobile-conn');
            if (mobConnEl) {
                mobConnEl.textContent = mobStatus.label || 'Belum Terhubung';
                mobConnEl.className = 'kpi-val ' + (mobState === 'active' ? 'kpi-safe' : (mobState === 'idle' ? '' : 'kpi-danger'));
                mobConnEl.style.color = mobState === 'idle' ? '#fbbf24' : '';
            }
            if (mobConnSubEl) {
                mobConnSubEl.textContent = mobStatus.connected
                    ? 'Aktif: ' + (mobStatus.active_devices ?? 0) + ' perangkat • ' + (mobStatus.last_endpoint || '-') + ' (' + (mobStatus.last_seen_at || '-') + ')'
                    : 'Belum ada request masuk dari Mobile APK ke /api';
            }
            if (mobConnBadgeEl) {
                mobConnBadgeEl.textContent = mobStatus.label || 'Belum Terhubung';
                mobConnBadgeEl.className = 'badge ' + (mobState === 'active' ? 'b-ok' : (mobState === 'idle' ? 'b-warn' : 'b-err'));
            }

            document.getElementById('kpi-db-status').textContent = (d.health?.db_status || '-') + ' (' + (d.health?.db_latency_ms ?? 0) + ' ms)';
            document.getElementById('kpi-srv-sub').textContent = 'PHP ' + (d.health?.php_version || '') + ' • RAM ' + (d.health?.memory_mb ?? 0) + ' MB • Disk Terpakai ' + (d.health?.disk_used_pct ?? 0) + '%';

            const isBlocked = !!(d.masterLockState && d.masterLockState.is_blocked);
            const failedCount = d.masterLockState?.failed_count ?? 0;
            const lockEl = document.getElementById('kpi-master-lock');
            if (lockEl) {
                lockEl.textContent = isBlocked ? 'TERBLOKIR (3/3)' : 'Normal / Aktif';
                lockEl.className = 'kpi-val ' + (isBlocked ? 'kpi-danger' : 'kpi-safe');
            }
            const masterSub = document.getElementById('kpi-master-sub');
            if (masterSub) {
                masterSub.textContent = 'Percobaan gagal: ' + failedCount + ' / 3';
            }
            const lockBanner = document.getElementById('master-lock-banner');
            if (lockBanner) {
                lockBanner.className = 'lock-banner ' + (isBlocked ? 'lock-danger' : 'lock-safe');
                if (isBlocked) {
                    lockBanner.innerHTML = '\x3cstrong\x3ePERINGATAN:\x3c/strong\x3e Akun Master Admin sedang \x3cstrong\x3eTERBLOKIR\x3c/strong\x3e karena salah data login 3 kali berturut-turut (terakhir: '
                        + esc(d.masterLockState?.last_email || '-') + ' pada ' + esc(d.masterLockState?.blocked_at || '-') + '). Gunakan form di bawah untuk mereset kata sandi &amp; membuka blokir.';
                } else {
                    lockBanner.innerHTML = 'Status login Master Admin saat ini \x3cstrong\x3eNormal\x3c/strong\x3e (percobaan gagal: ' + esc(failedCount) + ' / 3). Anda tetap dapat melakukan reset darurat jika Master Admin lupa kata sandi.';
                }
            }

            renderBoxTelemetry('tbody-web-telemetry', 'badge-count-web', webTelemetryList, 'Belum ada traffic Web.');
            renderBoxTelemetry('tbody-mobile-telemetry', 'badge-count-mobile', mobileTelemetryList, 'Belum ada traffic Mobile APK API.');
            renderKotakError();
            renderTabelBlockedIps();
            renderPermintaanResetMaster();
        }

        async function submitResetMasterPassword(e, formEl) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-reset-master');
            const fb = document.getElementById('master-reset-feedback');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            if (btn) { btn.disabled = true; btn.textContent = 'Memproses Reset...'; }

            try {
                const formData = new FormData(formEl);
                const res = await fetch(formEl.getAttribute('action'), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: formData
                });
                if (res.status === 401) {
                    window.location.href = '{{ route("developer.login") }}';
                    return false;
                }
                const json = await res.json();
                if (!res.ok || !json.success) {
                    if (fb) {
                        fb.className = 'alert-err';
                        fb.textContent = json?.message || 'Gagal mereset kata sandi Master Admin.';
                        fb.style.display = 'block';
                    }
                    return false;
                }
                if (json.data) {
                    terapkanSnapshotDashboard(json.data);
                }
                formEl.querySelectorAll('input[type="password"]').forEach(inp => { inp.value = ''; });
                if (fb) {
                    fb.className = 'alert-ok';
                    fb.textContent = json.message || 'Kata sandi Master Admin berhasil direset.';
                    fb.style.display = 'block';
                    setTimeout(() => { fb.style.display = 'none'; }, 6000);
                }
            } catch (err) {
                if (fb) {
                    fb.className = 'alert-err';
                    fb.textContent = 'Terjadi kesalahan koneksi saat mereset kata sandi.';
                    fb.style.display = 'block';
                }
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = 'Reset Kata Sandi & Buka Blokir Master Admin'; }
            }
            return false;
        }

        async function pollDeveloperRealtime() {
            if (isPollingDev) return;
            isPollingDev = true;
            try {
                const res = await fetch('{{ route("developer.dashboard") }}?_poll_dev_rt=1&_t=' + Date.now(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                if (res.status === 401) {
                    window.location.href = '{{ route("developer.login") }}';
                    return;
                }
                if (!res.ok) return;
                const json = await res.json();
                if (!json || !json.success || !json.data) return;

                terapkanSnapshotDashboard(json.data);
            } catch (e) {
            } finally {
                isPollingDev = false;
            }
        }

        async function manualRefreshDeveloper(btn) {
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Memuat...';
            }
            await pollDeveloperRealtime();
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Refresh';
            }
        }

        renderBoxTelemetry('tbody-web-telemetry', 'badge-count-web', webTelemetryList, 'Belum ada traffic Web.');
        renderBoxTelemetry('tbody-mobile-telemetry', 'badge-count-mobile', mobileTelemetryList, 'Belum ada traffic Mobile APK API.');
        renderKotakError();
        renderTabelBlockedIps();
        renderPermintaanResetMaster();
        setInterval(pollDeveloperRealtime, 2000);
    </script>
</body>
</html>
