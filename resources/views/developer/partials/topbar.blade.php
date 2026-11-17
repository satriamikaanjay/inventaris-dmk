<style>
    .dev-topbar { background: #0f172a; border-bottom: 1px solid #1e293b; padding: 12px 22px; display: flex; justify-content: space-between; align-items: center; gap: 12px; position: sticky; top: 0; z-index: 50; }
    .dev-brand { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .dev-brand img { width: 32px; height: 32px; border-radius: 8px; object-fit: contain; background: #fff; padding: 3px; flex-shrink: 0; }
    .dev-brand-txt { min-width: 0; }
    .dev-brand h1 { font-size: 15.5px; font-weight: 800; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dev-brand p { font-size: 11px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dev-nav-desktop { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .dev-nav-link { padding: 6px 12px; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: #cbd5e1; text-decoration: none; font-size: 11.5px; font-weight: 700; transition: all .15s; }
    .dev-nav-link:hover { border-color: #38bdf8; color: #38bdf8; }
    .dev-nav-link.active { background: rgba(56, 189, 248, 0.18); border-color: #38bdf8; color: #38bdf8; }
    .dev-btn-refresh { padding: 6px 12px; border-radius: 8px; border: 1px solid rgba(56,189,248,0.45); background: rgba(56,189,248,0.14); color: #7dd3fc; font-size: 11.5px; font-weight: 700; cursor: pointer; }
    .dev-btn-refresh:hover { background: #0284c7; color: #fff; }
    .dev-live-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 999px; background: rgba(16, 185, 129, 0.14); border: 1px solid rgba(16, 185, 129, 0.35); color: #34d399; font-size: 11px; font-weight: 700; }
    .dev-live-dot { width: 7px; height: 7px; border-radius: 50%; background: #10b981; animation: devPulse 1.5s infinite; }
    @keyframes devPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.35; } }
    .dev-btn-logout { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; padding: 6px 12px; border-radius: 8px; font-size: 11.5px; font-weight: 700; cursor: pointer; }
    .dev-btn-logout:hover { background: #dc2626; color: #fff; }
    .dev-btn-kunci { padding: 6px 12px; border-radius: 8px; font-size: 11.5px; font-weight: 800; cursor: pointer; border: 1px solid rgba(239,68,68,0.6); background: rgba(220,38,38,0.25); color: #fecaca; }
    .dev-btn-kunci.is-locked { border-color: rgba(16,185,129,0.55); background: rgba(16,185,129,0.2); color: #6ee7b7; }
    .dev-btn-kunci-mobile { width: 100%; padding: 10px 12px; border-radius: 9px; font-size: 12.5px; }
    .dev-mobile-controls { display: none; align-items: center; gap: 6px; flex-shrink: 0; }
    .dev-burger { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: #f8fafc; font-size: 16px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }

    .dev-drawer-backdrop { display: none; position: fixed; inset: 0; background: rgba(2, 6, 23, 0.72); z-index: 90; backdrop-filter: blur(2px); }
    .dev-drawer-backdrop.open { display: block; }
    .dev-drawer { position: fixed; top: 0; right: -290px; width: 270px; max-width: 85vw; height: 100vh; background: #0f172a; border-left: 1px solid #1e293b; z-index: 100; padding: 16px 14px; display: flex; flex-direction: column; gap: 10px; transition: right .22s ease; overflow-y: auto; }
    .dev-drawer.open { right: 0; }
    .dev-drawer-head { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid #1e293b; }
    .dev-drawer-title { font-size: 13px; font-weight: 800; color: #f8fafc; }
    .dev-drawer-close { width: 30px; height: 30px; border-radius: 7px; border: 1px solid #334155; background: #1e293b; color: #cbd5e1; font-size: 15px; cursor: pointer; }
    .dev-drawer-link { display: block; padding: 10px 12px; border-radius: 9px; border: 1px solid #1e293b; background: #111827; color: #cbd5e1; text-decoration: none; font-size: 12.5px; font-weight: 700; }
    .dev-drawer-link.active { background: rgba(56, 189, 248, 0.16); border-color: #38bdf8; color: #38bdf8; }

    @media (max-width: 960px) {
        .dev-topbar { padding: 10px 14px; }
        .dev-brand img { width: 28px; height: 28px; }
        .dev-brand h1 { font-size: 13px; }
        .dev-brand p { font-size: 10px; }
        .dev-nav-desktop { display: none; }
        .dev-mobile-controls { display: flex; }
        .dev-btn-refresh { padding: 6px 10px; font-size: 11px; }
    }
</style>

<header class="dev-topbar">
    <div class="dev-brand">
        <img src="{{ asset('logo.png') }}" alt="Logo">
        <div class="dev-brand-txt">
            <h1>{{ $pageTitle }}</h1>
            <p>{{ $pageSubtitle }}</p>
        </div>
    </div>

    <div class="dev-nav-desktop">
        <a href="{{ route('developer.dashboard') }}" class="dev-nav-link {{ $activeNav === 'dashboard' ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('developer.aktivitas') }}" class="dev-nav-link {{ $activeNav === 'aktivitas' ? 'active' : '' }}">Aktivitas &amp; Online</a>
        <a href="{{ route('developer.struktur') }}" class="dev-nav-link {{ $activeNav === 'struktur' ? 'active' : '' }}">Struktur Folder</a>
        <button type="button" class="dev-btn-refresh" onclick="{{ $refreshOnclick }}">Refresh</button>
        @if (!empty($showWebLock))
            <form method="POST" action="{{ route('developer.kunci_web') }}" style="margin:0;" onsubmit="return toggleKunciWebAjax(event, this)">
                @csrf
                <button type="submit" id="btn-kunci-web-desktop" data-locked="{{ !empty($webLocked) ? '1' : '0' }}" class="dev-btn-kunci {{ !empty($webLocked) ? 'is-locked' : '' }}">{{ !empty($webLocked) ? 'Buka Kunci Web' : 'Kunci Web' }}</button>
            </form>
        @endif
        @if (!empty($liveText))
            <span class="dev-live-pill"><span class="dev-live-dot"></span> {{ $liveText }}</span>
        @endif
        <form method="POST" action="{{ route('developer.logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="dev-btn-logout">Keluar</button>
        </form>
    </div>

    <div class="dev-mobile-controls">
        <button type="button" class="dev-btn-refresh" onclick="{{ $refreshOnclick }}">Refresh</button>
        <button type="button" class="dev-burger" onclick="toggleDevDrawer(true)" aria-label="Menu">☰</button>
    </div>
</header>

<div class="dev-drawer-backdrop" id="dev-drawer-backdrop" onclick="toggleDevDrawer(false)"></div>
<aside class="dev-drawer" id="dev-drawer">
    <div class="dev-drawer-head">
        <span class="dev-drawer-title">Menu Developer</span>
        <button type="button" class="dev-drawer-close" onclick="toggleDevDrawer(false)">✕</button>
    </div>

    @if (!empty($liveText))
        <span class="dev-live-pill" style="justify-content:center;"><span class="dev-live-dot"></span> {{ $liveText }}</span>
    @endif

    <a href="{{ route('developer.dashboard') }}" class="dev-drawer-link {{ $activeNav === 'dashboard' ? 'active' : '' }}">Dashboard Kesehatan</a>
    <a href="{{ route('developer.aktivitas') }}" class="dev-drawer-link {{ $activeNav === 'aktivitas' ? 'active' : '' }}">Aktivitas &amp; Online Akun</a>
    <a href="{{ route('developer.struktur') }}" class="dev-drawer-link {{ $activeNav === 'struktur' ? 'active' : '' }}">Struktur Folder &amp; Anti-Deface</a>

    @if (!empty($showWebLock))
        <form method="POST" action="{{ route('developer.kunci_web') }}" style="margin:4px 0 0;" onsubmit="return toggleKunciWebAjax(event, this)">
            @csrf
            <button type="submit" id="btn-kunci-web-mobile" data-locked="{{ !empty($webLocked) ? '1' : '0' }}" class="dev-btn-kunci dev-btn-kunci-mobile {{ !empty($webLocked) ? 'is-locked' : '' }}">{{ !empty($webLocked) ? 'Buka Kunci Web' : 'Kunci Web (Maintenance)' }}</button>
        </form>
    @endif

    <form method="POST" action="{{ route('developer.logout') }}" style="margin-top:auto;">
        @csrf
        <button type="submit" class="dev-btn-logout" style="width:100%; padding:10px 12px; font-size:12.5px;">Keluar Console</button>
    </form>
</aside>

<script>
    function toggleDevDrawer(open) {
        const d = document.getElementById('dev-drawer');
        const b = document.getElementById('dev-drawer-backdrop');
        if (!d || !b) return;
        d.classList.toggle('open', !!open);
        b.classList.toggle('open', !!open);
    }

    function sinkronTombolKunciWeb(isLocked) {
        const btnDesk = document.getElementById('btn-kunci-web-desktop');
        const btnMob = document.getElementById('btn-kunci-web-mobile');
        [
            { el: btnDesk, labelLock: 'Kunci Web', labelUnlock: 'Buka Kunci Web' },
            { el: btnMob, labelLock: 'Kunci Web (Maintenance)', labelUnlock: 'Buka Kunci Web' },
        ].forEach(cfg => {
            if (!cfg.el) return;
            cfg.el.setAttribute('data-locked', isLocked ? '1' : '0');
            cfg.el.textContent = isLocked ? cfg.labelUnlock : cfg.labelLock;
            cfg.el.classList.toggle('is-locked', !!isLocked);
        });
    }

    async function toggleKunciWebAjax(e, formEl) {
        e.preventDefault();
        const btnDesk = document.getElementById('btn-kunci-web-desktop');
        const curLocked = (btnDesk?.getAttribute('data-locked') === '1');
        const msgKonfirm = curLocked
            ? 'Buka kunci web sekarang? Seluruh pengguna akan kembali bisa mengakses web.'
            : 'Kunci seluruh akses web sekarang? Semua pengguna (selain Developer Console) akan dialihkan ke halaman Maintenance.';
        if (!confirm(msgKonfirm)) return false;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || formEl.querySelector('input[name="_token"]')?.value || '';
        const submitBtn = formEl.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const res = await fetch(formEl.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                },
                body: new FormData(formEl)
            });
            if (res.status === 401) {
                window.location.href = '{{ route("developer.login") }}';
                return false;
            }
            const json = await res.json();
            if (res.ok && json && json.success) {
                sinkronTombolKunciWeb(!!json.webLocked);
                if (typeof window.onWebLockUpdated === 'function') {
                    window.onWebLockUpdated(!!json.webLocked, json.webLockInfo || {}, json.message || '');
                }
            }
        } catch (err) {
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
        return false;
    }
</script>
