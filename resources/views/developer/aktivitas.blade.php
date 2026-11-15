<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivitas Sekilas Semua Akun — Developer Console</title>
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
        .nav-link { padding: 7px 13px; border-radius: 9px; border: 1px solid #334155; background: #1e293b; color: #e2e8f0; text-decoration: none; font-size: 12px; font-weight: 700; }
        .nav-link:hover { border-color: #38bdf8; color: #38bdf8; }
        .live-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: rgba(16, 185, 129, 0.14); border: 1px solid rgba(16, 185, 129, 0.35); color: #34d399; font-size: 11.5px; font-weight: 700; }
        .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.35; } }
        .btn-logout { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; padding: 7px 14px; border-radius: 9px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn-logout:hover { background: #dc2626; color: #fff; }
        .container { max-width: 1440px; margin: 0 auto; padding: 22px 24px 48px; }
        .info-bar { background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.3); color: #bae6fd; padding: 11px 15px; border-radius: 12px; font-size: 12.5px; margin-bottom: 18px; }
        .tri-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr)); gap: 18px; }
        .panel { background: #111827; border: 1px solid #1e293b; border-radius: 14px; padding: 18px; }
        .panel-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
        .panel-head h2 { font-size: 14.5px; font-weight: 800; color: #f8fafc; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .b-master { background: rgba(245, 158, 11, 0.18); color: #fbbf24; }
        .b-admin { background: rgba(16, 185, 129, 0.18); color: #34d399; }
        .b-user { background: rgba(168, 85, 247, 0.18); color: #c084fc; }
        .scroll-box { max-height: 520px; overflow-y: auto; overflow-x: hidden; border: 1px solid #1e293b; border-radius: 10px; background: #0b1120; }
        .scroll-box::-webkit-scrollbar { width: 7px; }
        .scroll-box::-webkit-scrollbar-thumb { background: #334155; border-radius: 999px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { position: sticky; top: 0; z-index: 2; text-align: left; padding: 9px 11px; background: #0f172a; color: #94a3b8; font-size: 10.5px; text-transform: uppercase; border-bottom: 1px solid #1e293b; }
        td { padding: 10px 11px; border-bottom: 1px solid #1e293b; color: #e2e8f0; vertical-align: top; line-height: 1.45; word-break: break-word; overflow-wrap: anywhere; }
        .uname { font-weight: 700; color: #f8fafc; }
        .tgl { color: #94a3b8; font-size: 11px; white-space: nowrap; }
        @media (max-width: 768px) {
            .container { padding: 12px 10px 32px; }
            .info-bar { font-size: 11px; padding: 9px 11px; margin-bottom: 12px; }
            .tri-grid { gap: 12px; }
            .panel { padding: 12px 10px; border-radius: 11px; }
            .panel-head h2 { font-size: 12.5px; }
            .badge { font-size: 9.5px; padding: 2px 7px; }
            .scroll-box { max-height: 380px; }
            .tgl { white-space: normal; font-size: 10px; }
            table, thead, tbody, tr, td { display: block; width: 100%; }
            thead { display: none; }
            tbody tr { padding: 8px 10px; border-bottom: 1px solid #1e293b; }
            tbody tr:last-child { border-bottom: none; }
            tbody td { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; padding: 3px 0; border-bottom: none; text-align: right; font-size: 11px; }
            tbody td[data-label]::before { content: attr(data-label); font-size: 9.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; text-align: left; flex-shrink: 0; }
            tbody td[colspan] { display: block; text-align: center !important; }
        }
    </style>
</head>
<body>
    @include('developer.partials.topbar', [
        'pageTitle'      => 'Aktivitas Akun (3 Role)',
        'pageSubtitle'   => 'Ringkasan aktivitas Master Admin, Admin & User Mobile',
        'activeNav'      => 'aktivitas',
        'refreshOnclick' => 'manualRefreshAktivitas(this)',
        'liveText'       => 'Live (1 dtk)',
    ])

    <div class="container">
        <div class="info-bar">
            Halaman ini menampilkan <strong>gambaran sekilas aktivitas</strong> yang dilakukan oleh masing-masing role (hanya username, ringkasan aksi, dan tanggal). Data sensitif seperti email, password, maupun rincian transaksi tidak ditampilkan.
        </div>

        <div class="tri-grid">
            <section class="panel" style="border-top: 3px solid #f59e0b;">
                <div class="panel-head">
                    <h2><span class="badge b-master">MASTER ADMIN</span> Aktivitas Master Admin</h2>
                    <span class="badge b-master" id="cnt-master">{{ count($masterLogs) }}</span>
                </div>
                <div class="scroll-box">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Aktivitas Sekilas</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-master-logs"></tbody>
                    </table>
                </div>
            </section>

            <section class="panel" style="border-top: 3px solid #10b981;">
                <div class="panel-head">
                    <h2><span class="badge b-admin">ADMIN</span> Aktivitas Admin</h2>
                    <span class="badge b-admin" id="cnt-admin">{{ count($adminLogs) }}</span>
                </div>
                <div class="scroll-box">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Aktivitas Sekilas</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-admin-logs"></tbody>
                    </table>
                </div>
            </section>

            <section class="panel" style="border-top: 3px solid #c084fc;">
                <div class="panel-head">
                    <h2><span class="badge b-user">USER MOBILE</span> Aktivitas User</h2>
                    <span class="badge b-user" id="cnt-user">{{ count($userLogs) }}</span>
                </div>
                <div class="scroll-box">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Aktivitas Sekilas</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-user-logs"></tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <input type="hidden" id="init-master-logs" value="{{ json_encode($masterLogs) }}">
    <input type="hidden" id="init-admin-logs" value="{{ json_encode($adminLogs) }}">
    <input type="hidden" id="init-user-logs" value="{{ json_encode($userLogs) }}">

    <script>
        let masterLogs = JSON.parse(document.getElementById('init-master-logs').value || '[]');
        let adminLogs = JSON.parse(document.getElementById('init-admin-logs').value || '[]');
        let userLogs = JSON.parse(document.getElementById('init-user-logs').value || '[]');

        function esc(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function renderPanel(tbodyId, cntId, list, emptyMsg) {
            const tbody = document.getElementById(tbodyId);
            const cnt = document.getElementById(cntId);
            if (cnt) cnt.textContent = list.length + ' Aksi';
            if (!tbody) return;

            if (list.length === 0) {
                tbody.innerHTML = '\x3ctr\x3e\x3ctd colspan="3" style="text-align:center; color:#64748b; padding:24px;"\x3e' + esc(emptyMsg) + '\x3c/td\x3e\x3c/tr\x3e';
                return;
            }

            tbody.innerHTML = list.map(item => {
                return '\x3ctr\x3e'
                    + '\x3ctd data-label="Akun" class="uname"\x3e' + esc(item.username) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Aktivitas"\x3e' + esc(item.ringkasan) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Tanggal" class="tgl"\x3e' + esc(item.tanggal) + '\x3c/td\x3e'
                    + '\x3c/tr\x3e';
            }).join('');
        }

        async function pollAktivitas() {
            try {
                const res = await fetch('{{ route("developer.aktivitas") }}?_poll_dev_act=1&_t=' + Date.now(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const json = await res.json();
                if (!json || !json.success || !json.data) return;

                masterLogs = json.data.masterLogs || [];
                adminLogs = json.data.adminLogs || [];
                userLogs = json.data.userLogs || [];

                renderPanel('tbody-master-logs', 'cnt-master', masterLogs, 'Belum ada aktivitas Master Admin.');
                renderPanel('tbody-admin-logs', 'cnt-admin', adminLogs, 'Belum ada aktivitas Admin.');
                renderPanel('tbody-user-logs', 'cnt-user', userLogs, 'Belum ada aktivitas User Mobile.');
            } catch (e) {
            }
        }

        async function manualRefreshAktivitas(btn) {
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Memuat...';
            }
            await pollAktivitas();
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Refresh';
            }
        }

        renderPanel('tbody-master-logs', 'cnt-master', masterLogs, 'Belum ada aktivitas Master Admin.');
        renderPanel('tbody-admin-logs', 'cnt-admin', adminLogs, 'Belum ada aktivitas Admin.');
        renderPanel('tbody-user-logs', 'cnt-user', userLogs, 'Belum ada aktivitas User Mobile.');
        setInterval(pollAktivitas, 1000);
    </script>
</body>
</html>
