<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivitas &amp; Status Online Akun — Developer Console</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        body { background: #0b1120; color: #e2e8f0; min-height: 100vh; }
        .container { max-width: 1440px; margin: 0 auto; padding: 22px 24px 48px; }
        .info-bar { background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.3); color: #bae6fd; padding: 11px 15px; border-radius: 12px; font-size: 12.5px; margin-bottom: 18px; }
        .tri-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr)); gap: 18px; }
        .panel { background: #111827; border: 1px solid #1e293b; border-radius: 14px; padding: 18px; }
        .panel-presence { margin-bottom: 18px; border-top: 3px solid #10b981; }
        .panel-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
        .panel-head h2 { font-size: 14.5px; font-weight: 800; color: #f8fafc; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .filter-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .filter-control { padding: 6px 10px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; font-size: 12px; outline: none; }
        .filter-control:focus { border-color: #38bdf8; }
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
        .b-master { background: rgba(245, 158, 11, 0.18); color: #fbbf24; }
        .b-admin { background: rgba(16, 185, 129, 0.18); color: #34d399; }
        .b-user { background: rgba(168, 85, 247, 0.18); color: #c084fc; }
        .b-ok { background: rgba(16, 185, 129, 0.16); color: #34d399; }
        .b-role { background: rgba(59, 130, 246, 0.18); color: #93c5fd; }
        .b-offline { background: rgba(100, 116, 139, 0.18); color: #94a3b8; }
        .dot-on { width: 7px; height: 7px; border-radius: 50%; background: #10b981; display: inline-block; }
        .dot-off { width: 7px; height: 7px; border-radius: 50%; background: #64748b; display: inline-block; }
        .scroll-box { max-height: 520px; overflow-y: auto; overflow-x: hidden; border: 1px solid #1e293b; border-radius: 10px; background: #0b1120; }
        .scroll-box-presence { max-height: 320px; }
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
            .panel-presence { margin-bottom: 12px; }
            .panel-head h2 { font-size: 12.5px; }
            .filter-bar { width: 100%; }
            .filter-control { flex: 1 1 130px; font-size: 11px; padding: 6px 8px; }
            .badge { font-size: 9.5px; padding: 2px 7px; }
            .scroll-box { max-height: 380px; }
            .scroll-box-presence { max-height: 340px; }
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
        'pageTitle'      => 'Aktivitas &amp; Status Online Akun',
        'pageSubtitle'   => 'Status Online/Offline Live Per Akun &amp; Ringkasan Aktivitas (Master Admin, Admin, User Mobile)',
        'activeNav'      => 'aktivitas',
        'refreshOnclick' => 'manualRefreshAktivitas(this)',
        'liveText'       => 'Live (1 dtk)',
    ])

    <div class="container">
        <div class="info-bar">
            Halaman ini menyatukan pemantauan <strong>status Online / Offline live per akun</strong> serta <strong>gambaran sekilas aktivitas</strong> masing-masing role (hanya username, status live, ringkasan aksi, dan waktu). Data sensitif seperti email maupun password tetap dilindungi.
        </div>

        <section class="panel panel-presence">
            <div class="panel-head">
                <h2>Status Online / Offline Per Akun (Live • Privasi Terjaga)</h2>
                <div class="filter-bar">
                    <span class="badge b-ok" id="badge-online-count">0 Online</span>
                    <span class="badge b-offline" id="badge-offline-count">0 Offline</span>
                    <span class="badge b-role" id="badge-total-accounts">{{ count($allRolesUsers ?? []) }} Akun</span>
                    <span class="badge b-role">Email &amp; Password Disembunyikan</span>
                    <input type="text" id="filter-search-akun" class="filter-control" placeholder="Cari username..." oninput="renderTabelRoles()" style="min-width: 150px;">
                    <select id="filter-status-select" class="filter-control" onchange="renderTabelRoles()">
                        <option value="all">Semua Status</option>
                        <option value="online">Online Saja</option>
                        <option value="offline">Offline Saja</option>
                    </select>
                    <select id="filter-role-select" class="filter-control" onchange="renderTabelRoles()">
                        <option value="all">Semua Role</option>
                        <option value="master_admin">Master Admin</option>
                        <option value="admin">Admin</option>
                        <option value="user">User (Mobile)</option>
                        <option value="developer">Developer</option>
                    </select>
                </div>
            </div>
            <div class="scroll-box scroll-box-presence">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status Live</th>
                            <th>Terakhir Terlihat</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Terdaftar</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-roles"></tbody>
                </table>
            </div>
        </section>

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

    <input type="hidden" id="init-roles" value="{{ json_encode($allRolesUsers ?? []) }}">
    <input type="hidden" id="init-master-logs" value="{{ json_encode($masterLogs) }}">
    <input type="hidden" id="init-admin-logs" value="{{ json_encode($adminLogs) }}">
    <input type="hidden" id="init-user-logs" value="{{ json_encode($userLogs) }}">

    <script>
        let rolesList = JSON.parse(document.getElementById('init-roles').value || '[]');
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

        function renderTabelRoles() {
            const tbody = document.getElementById('tbody-roles');
            const filterRole = document.getElementById('filter-role-select')?.value || 'all';
            const filterStatus = document.getElementById('filter-status-select')?.value || 'all';
            const searchQuery = (document.getElementById('filter-search-akun')?.value || '').trim().toLowerCase();

            const badgeOn = document.getElementById('badge-online-count');
            const badgeOff = document.getElementById('badge-offline-count');
            const badgeTotal = document.getElementById('badge-total-accounts');

            const totalOnline = rolesList.filter(r => !!r.is_online).length;
            const totalOffline = Math.max(0, rolesList.length - totalOnline);
            if (badgeOn) badgeOn.textContent = totalOnline + ' Online';
            if (badgeOff) badgeOff.textContent = totalOffline + ' Offline';
            if (badgeTotal) badgeTotal.textContent = rolesList.length + ' Akun';
            if (!tbody) return;

            const filtered = rolesList.filter(r => {
                if (filterRole !== 'all' && r.role !== filterRole) return false;
                if (filterStatus === 'online' && !r.is_online) return false;
                if (filterStatus === 'offline' && !!r.is_online) return false;
                if (searchQuery !== '' && !String(r.username || '').toLowerCase().includes(searchQuery)) return false;
                return true;
            });

            if (filtered.length === 0) {
                tbody.innerHTML = '\x3ctr\x3e\x3ctd colspan="8" style="text-align:center; color:#64748b; padding:22px;"\x3eTidak ada akun yang cocok dengan filter saat ini.\x3c/td\x3e\x3c/tr\x3e';
                return;
            }

            tbody.innerHTML = filtered.map((r, i) => {
                let statusHtml = '\x3cspan class="badge b-offline"\x3e\x3cspan class="dot-off"\x3e\x3c/span\x3e Offline\x3c/span\x3e';
                if (r.is_online) {
                    const via = r.online_channel === 'mobile' ? 'Mobile APK' : 'Web';
                    statusHtml = '\x3cspan class="badge b-ok"\x3e\x3cspan class="dot-on"\x3e\x3c/span\x3e Online (' + via + ')\x3c/span\x3e';
                }

                return '\x3ctr\x3e'
                    + '\x3ctd data-label="No"\x3e' + (i + 1) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Username" class="uname"\x3e' + esc(r.username) + '\x3c/td\x3e'
                    + '\x3ctd data-label="Role"\x3e\x3cspan class="badge b-role"\x3e' + esc(r.role) + '\x3c/span\x3e\x3c/td\x3e'
                    + '\x3ctd data-label="Status"\x3e' + statusHtml + '\x3c/td\x3e'
                    + '\x3ctd data-label="Terakhir Terlihat" class="tgl"\x3e' + esc(r.last_seen_at || '-') + '\x3c/td\x3e'
                    + '\x3ctd data-label="Email" style="color:#64748b; font-style:italic;"\x3e[Dilindungi]\x3c/td\x3e'
                    + '\x3ctd data-label="Password" style="color:#64748b; font-style:italic;"\x3e[Dilindungi]\x3c/td\x3e'
                    + '\x3ctd data-label="Terdaftar" class="tgl"\x3e' + esc(r.created_at) + '\x3c/td\x3e'
                    + '\x3c/tr\x3e';
            }).join('');
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

        let isPollingAct = false;

        async function pollAktivitas() {
            if (isPollingAct) return;
            isPollingAct = true;
            try {
                const res = await fetch('{{ route("developer.aktivitas") }}?_poll_dev_act=1&_t=' + Date.now(), {
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

                rolesList = json.data.allRolesUsers || [];
                masterLogs = json.data.masterLogs || [];
                adminLogs = json.data.adminLogs || [];
                userLogs = json.data.userLogs || [];

                renderTabelRoles();
                renderPanel('tbody-master-logs', 'cnt-master', masterLogs, 'Belum ada aktivitas Master Admin.');
                renderPanel('tbody-admin-logs', 'cnt-admin', adminLogs, 'Belum ada aktivitas Admin.');
                renderPanel('tbody-user-logs', 'cnt-user', userLogs, 'Belum ada aktivitas User Mobile.');
            } catch (e) {
            } finally {
                isPollingAct = false;
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

        renderTabelRoles();
        renderPanel('tbody-master-logs', 'cnt-master', masterLogs, 'Belum ada aktivitas Master Admin.');
        renderPanel('tbody-admin-logs', 'cnt-admin', adminLogs, 'Belum ada aktivitas Admin.');
        renderPanel('tbody-user-logs', 'cnt-user', userLogs, 'Belum ada aktivitas User Mobile.');
        setInterval(pollAktivitas, 1000);
    </script>
</body>
</html>
