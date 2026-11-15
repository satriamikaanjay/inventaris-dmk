<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Security Console — Inventaris DMK</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        body { min-height: 100vh; background: radial-gradient(circle at top right, #1e293b 0%, #0f172a 55%, #020617 100%); color: #e2e8f0; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .dev-card { width: 100%; max-width: 440px; background: rgba(15, 23, 42, 0.92); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 18px; padding: 30px; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.55); }
        .dev-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: rgba(56, 189, 248, 0.14); border: 1px solid rgba(56, 189, 248, 0.35); color: #38bdf8; font-size: 11px; font-weight: 700; letter-spacing: 0.4px; margin-bottom: 14px; }
        h1 { font-size: 22px; font-weight: 800; color: #f8fafc; margin-bottom: 6px; }
        .sub { font-size: 13px; color: #94a3b8; margin-bottom: 22px; line-height: 1.5; }
        .alert-err { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 10px 13px; border-radius: 10px; font-size: 12.5px; margin-bottom: 16px; }
        .fg { margin-bottom: 15px; }
        label { display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px; }
        input { width: 100%; padding: 11px 13px; border-radius: 10px; border: 1px solid rgba(148, 163, 184, 0.25); background: #020617; color: #f8fafc; font-size: 13.5px; outline: none; transition: border-color .2s; }
        input:focus { border-color: #38bdf8; }
        .btn-submit { width: 100%; padding: 12px; border: none; border-radius: 10px; background: linear-gradient(135deg, #0284c7, #2563eb); color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; margin-top: 6px; transition: opacity .2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit:hover { opacity: 0.92; }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
        .dev-spinner { width: 16px; height: 16px; border: 2.5px solid rgba(255, 255, 255, 0.35); border-top-color: #fff; border-radius: 50%; animation: devSpin .7s linear infinite; display: none; }
        .btn-submit.is-loading .dev-spinner { display: inline-block; }
        @keyframes devSpin { to { transform: rotate(360deg); } }
        .cf-box { margin: 14px 0; padding: 10px; border-radius: 10px; background: rgba(2, 6, 23, 0.7); border: 1px solid rgba(148, 163, 184, 0.2); }
        .foot-note { margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(148, 163, 184, 0.15); font-size: 11.5px; color: #64748b; display: flex; justify-content: space-between; align-items: center; }
        .foot-note a { color: #94a3b8; text-decoration: none; }
        .foot-note a:hover { color: #38bdf8; }
    </style>
</head>
<body>
    <div class="dev-card">
        <div class="dev-badge">PORTAL TERISOLASI • DEVELOPER ONLY</div>
        <h1>Developer Security Console</h1>
        <p class="sub">Akses khusus pengembang sistem untuk pemantauan kesehatan Web & Mobile API, log error, dan pemulihan darurat Master Admin.</p>

        @if ($errors->any())
            <div class="alert-err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('developer.login.proses') }}" id="dev-login-form">
            @csrf
            <div class="fg">
                <label>Username Developer</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="developer" required autocomplete="username">
            </div>
            <div class="fg">
                <label>Password Akun</label>
                <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <div class="fg">
                <label>Developer Access Key</label>
                <input type="password" name="access_key" placeholder="Masukkan kunci akses developer (.env)" required autocomplete="off">
            </div>

            @if (config('services.turnstile.site_key'))
                <div class="cf-box">
                    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="dark" data-size="flexible"></div>
                </div>
            @endif

            <button type="submit" class="btn-submit" id="dev-login-btn">
                <span class="dev-spinner"></span>
                <span id="dev-login-text">Verifikasi & Masuk Console</span>
            </button>
        </form>

        <div class="foot-note">
            <span>Dilindungi Rate Limiter + Access Key</span>
            <a href="{{ route('login') }}">← Kembali ke Login Utama</a>
        </div>
    </div>

    @if (config('services.turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    <script>
        (function () {
            const form = document.getElementById('dev-login-form');
            const btn = document.getElementById('dev-login-btn');
            const textEl = document.getElementById('dev-login-text');
            if (!form || !btn || !textEl) return;

            form.addEventListener('submit', function (e) {
                if (form.dataset.submitting === '1') {
                    e.preventDefault();
                    return;
                }
                form.dataset.submitting = '1';
                btn.disabled = true;
                btn.classList.add('is-loading');
                textEl.textContent = 'Memverifikasi...';
                form.querySelectorAll('input').forEach(function (inp) {
                    inp.readOnly = true;
                });
            });

            window.addEventListener('pageshow', function () {
                form.dataset.submitting = '0';
                btn.disabled = false;
                btn.classList.remove('is-loading');
                textEl.textContent = 'Verifikasi & Masuk Console';
                form.querySelectorAll('input').forEach(function (inp) {
                    inp.readOnly = false;
                });
            });
        })();
    </script>
</body>
</html>
