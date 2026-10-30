@php
    $lockFilePath = storage_path('app/admin_login_locks.json');
    $lockData = [
        'failed_count' => 0,
        'is_blocked'   => false,
        'last_email'   => '',
        'blocked_at'   => null,
        'updated_at'   => 0,
    ];

    if (is_file($lockFilePath)) {
        $decodedLock = json_decode((string) file_get_contents($lockFilePath), true);
        if (is_array($decodedLock)) {
            $lockData = array_merge($lockData, $decodedLock);
        }
    }

    // Endpoint AJAX ringan untuk sinkronisasi jumlah gagal login & status blokir Admin
    if (request()->has('_login_lock_action')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $act = (string) request('_login_lock_action');

        if ($act === 'status') {
            echo json_encode(['success' => true, 'lock' => $lockData]);
            exit;
        }

        if ($act === 'record_fail') {
            $emailGagal = trim((string) request('email', ''));
            $lockData['failed_count'] = ((int) ($lockData['failed_count'] ?? 0)) + 1;
            if ($emailGagal !== '') {
                $lockData['last_email'] = $emailGagal;
            }
            $baruTerblokir = false;
            if ($lockData['failed_count'] >= 5) {
                if (empty($lockData['is_blocked'])) {
                    $baruTerblokir = true;
                }
                $lockData['is_blocked'] = true;
                $lockData['blocked_at'] = now()->format('d M Y, H:i:s');
            }
            $lockData['updated_at'] = round(microtime(true) * 1000);

            try {
                file_put_contents($lockFilePath, json_encode($lockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
            }

            // Catat ke RiwayatAktivitas agar Master Admin mendapat notifikasi real-time
            if ($baruTerblokir && class_exists(\App\Models\RiwayatAktivitas::class)) {
                try {
                    $riwayat = new \App\Models\RiwayatAktivitas();
                    $tbl = $riwayat->getTable();
                    $dataLog = [];
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'user_id')) {
                        $dataLog['user_id'] = null;
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'aktivitas')) {
                        $infoEmail = $emailGagal !== '' ? (' (' . $emailGagal . ')') : '';
                        $dataLog['aktivitas'] = 'Peringatan Keamanan: Akses login Admin terblokir setelah 5 kali salah memasukkan data/kata sandi' . $infoEmail . '.';
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'modul')) {
                        $dataLog['modul'] = 'Autentikasi Sistem';
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'status')) {
                        $dataLog['status'] = 'Peringatan';
                    }
                    $riwayat->forceFill($dataLog)->save();
                } catch (\Throwable $e) {
                }
            }

            echo json_encode(['success' => true, 'lock' => $lockData]);
            exit;
        }

        if ($act === 'reset_lock') {
            $lockData = [
                'failed_count' => 0,
                'is_blocked'   => false,
                'last_email'   => '',
                'blocked_at'   => null,
                'updated_at'   => round(microtime(true) * 1000),
            ];
            try {
                file_put_contents($lockFilePath, json_encode($lockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
            }
            echo json_encode(['success' => true, 'lock' => $lockData]);
            exit;
        }

        echo json_encode(['success' => false]);
        exit;
    }

    $adaErrorLoginServer = $errors->any();
    $oldRoleLogin = old('login_as', '');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Master Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #0d2642;
            color: #fff;
            width: 100vw;
            height: 100vh; /* Kunci tinggi layar tepat 100% */
            overflow: hidden; /* Hilangkan scrollbar pada desktop */
        }

        .login-master-admin {
            display: flex;
            width: 100%;
            height: 100%;
        }

        /* Panel Kiri (Informasi) */
        .info-panel {
            flex: 1;
            padding: 4% 6%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .brand-header {
            margin-bottom: 2rem;
        }

        .pt-name {
            display: block;
            font-size: 22px;
            font-weight: 700;
        }

        .logistics-solutions {
            font-size: 13px;
            color: #ef9b0c;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .hero-section {
            margin-bottom: 2.5rem;
        }

        .platfrom-terpercaya {
            color: #f29c0c;
            font-size: 14px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        .kirim-kargo-anda {
            font-size: clamp(28px, 3.5vw, 42px);
            line-height: 1.2;
            margin-bottom: 1rem;
        }

        .kelola-pengiriman-kargo {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.5;
            max-width: 450px;
        }

        .features-container {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .feature-card {
            background-color: rgba(217, 217, 217, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 1rem;
            flex: 1;
            min-width: 140px;
            box-shadow: 5px 4px 10px rgba(0, 0, 0, 0.25);
        }

        .feature-title {
            font-size: 14px;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .feature-stat {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 500;
        }

        /* Panel Kanan (Form Login) */
        .login-panel {
            flex: 1;
            background-color: #fff;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 2rem;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background-color: #fff;
            border-radius: 15px;
            padding: 2rem 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .selamat-datang-kembali {
            font-size: 22px;
            margin-bottom: 0.5rem;
        }

        .masuk-ke-akun-desc {
            font-size: 13px;
            color: rgba(0, 0, 0, 0.5);
        }

        .role-toggle {
            display: flex;
            background-color: #f0f0f0;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .role-btn {
            flex: 1;
            padding: 0.7rem;
            border: none;
            background: none;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-btn.active {
            background-color: #008ce2;
            color: #fff;
            border-radius: 10px;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            font-size: 13px;
            outline: none;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            border-color: #008ce2;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            margin-bottom: 1.5rem;
        }

        .ingat-saya {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: rgba(0, 0, 0, 0.6);
        }

        .right-options {
            display: flex;
            gap: 0.8rem;
        }

        .koneksi-aman {
            color: rgba(0, 0, 0, 0.5);
        }

        .lupa-password {
            color: #008ce2;
            text-decoration: none;
            font-weight: 500;
        }

        .submit-btn {
            width: 100%;
            padding: 0.8rem;
            background-color: #008ce2;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-bottom: 1.2rem;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #007bb5;
        }

        .toast-notification {
    position: fixed;
    top: 25px;
    right: -100%; /* Mulai dari luar layar sebelah kanan */
    background-color: #fee2e2;
    color: #dc2626;
    padding: 15px 25px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(220, 38, 38, 0.2);
    font-size: 14px;
    font-weight: 600;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: right 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); /* Efek memantul sedikit saat muncul */
}

.toast-notification.show {
    right: 25px; /* Posisi saat muncul di layar */
}

/* --- ANIMASI LOADING BUTTON --- */
.spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
    margin-right: 8px;
    vertical-align: middle;
}

.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-wrapper .form-input {
    padding-right: 40px; /* Memberi ruang di sebelah kanan agar teks tidak tertimpa ikon */
}

.toggle-password-btn {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    color: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: color 0.3s;
}

.toggle-password-btn:hover {
    color: #008ce2;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.submit-btn:disabled {
    background-color: #006b9b;
    cursor: not-allowed;
    opacity: 0.8;
}

        .halaman-ini-diperuntukkan {
            font-size: 11px;
            text-align: center;
            color: rgba(0, 0, 0, 0.5);
            line-height: 1.4;
        }

        /* Mode Mobile (Menyembunyikan panel kiri, hanya tampilkan login) */
        @media screen and (max-width: 968px) {
            body {
                background-color: #fff;
                overflow-y: auto; /* Aktifkan scroll di mobile jika ukuran layar ponsel terlalu kecil untuk form */
            }

            .info-panel {
                display: none; /* Sembunyikan informasi kargo sepenuhnya */
            }

            .login-panel {
                width: 100%;
                min-height: 100vh;
                padding: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .login-card {
                padding: 1.5rem;
                box-shadow: none; /* Tampilan bersih tanpa bayangan berlebih di layar kecil */
            }
        }
            /* --- LABEL BLOKIR LOGIN ADMIN (5X GAGAL) --- */
        .admin-blocked-box {
            display: none;
            background-color: #fef2f2;
            border: 2px solid #ef4444;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 1.2rem;
            color: #991b1b;
            animation: shake-lock 0.4s ease-in-out;
        }
        .admin-blocked-box.show {
            display: block;
        }
        .admin-blocked-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            color: #dc2626;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .admin-blocked-desc {
            font-size: 12px;
            line-height: 1.5;
            color: #7f1d1d;
            font-weight: 500;
        }
        .admin-blocked-contact {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #fca5a5;
            font-size: 12px;
            font-weight: 700;
            color: #b91c1c;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .admin-attempt-warning {
            display: none;
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
            color: #92400e;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        .admin-attempt-warning.show {
            display: block;
        }
        .form-input:disabled {
            background-color: #f1f5f9 !important;
            color: #94a3b8 !important;
            cursor: not-allowed !important;
            border-color: #cbd5e1 !important;
        }
        .submit-btn.blocked-btn {
            background-color: #dc2626 !important;
            color: #fff !important;
            cursor: not-allowed !important;
            opacity: 0.9 !important;
        }
        @keyframes shake-lock {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
    </style>
</head>
<body>
    <div class="login-master-admin">
        <!-- Bagian Kiri: Informasi Kargo (Hidden on Mobile) -->
        <div class="info-panel">
            <div class="brand-header">
                <div class="brand-text">
                    <span class="pt-name">PT. Dian Mega Kurnia</span>
                    <span class="logistics-solutions">LOGISTICS SOLUTIONS</span>
                </div>
            </div>
            
            <div class="hero-section">
                <div class="trust-badge">
                    <span class="platfrom-terpercaya">Platform Terpercaya</span>
                </div>
                <h1 class="kirim-kargo-anda">Kirim Kargo Anda<br>Ke Seluruh Dunia</h1>
                <p class="kelola-pengiriman-kargo">Kelola pengiriman kargo laut, udara, dan darat Anda<br>dalam satu platform. Cepat, aman, dan terpercaya.</p>
            </div>

            <div class="features-container">
                <div class="feature-card">
                    <h3 class="feature-title">Terpercaya<br>Keamanan Terjamin</h3>
                    <p class="feature-stat">500+ Klien Aktif</p>
                </div>
                <div class="feature-card">
                    <h3 class="feature-title">Cepat<br>Kiriman Tepat Waktu</h3>
                    <p class="feature-stat">99.8% On-Time</p>
                </div>
                <div class="feature-card">
                    <h3 class="feature-title">Global<br>Jangkauan 200+ negara</h3>
                    <p class="feature-stat">4,9/5 Rating</p>
                </div>
            </div>
        </div>

        <!-- Bagian Kanan: Form Login (Selalu Tampil) -->
        <div class="login-panel">
            <div class="login-card">
                <div class="login-header">
                    <h2 class="selamat-datang-kembali">Selamat Datang Kembali</h2>
                    <p class="masuk-ke-akun-desc">Masuk ke akun Anda untuk mengelola pengiriman kargo</p>
                </div>

                <!-- Tombol Role -->
                <div class="role-toggle">
                    <button type="button" class="role-btn" id="btn-admin">Admin</button>
                    <button type="button" class="role-btn active" id="btn-master">Master Admin</button>
                </div>

                <!-- Form Login -->
                <form class="login-form" action="{{ route('login.proses') }}" method="POST" id="form-login">
                    @csrf
                    <input type="hidden" name="login_as" id="login_as" value="master_admin">

                    <!-- AREA NOTIFIKASI MENGAMBANG -->
                    @if ($errors->any())
                        <div id="error-toast" class="toast-notification">
                            <!-- Ikon Peringatan -->
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <!-- LABEL PERINGATAN PERCOBAAN GAGAL (1 s/d 4 KALI) -->
                    <div id="admin-attempt-warning" class="admin-attempt-warning"></div>

                    <!-- LABEL TERBLOKIR SETELAH 5 KALI SALAH (KHUSUS ADMIN) -->
                    <div id="admin-blocked-box" class="admin-blocked-box">
                        <div class="admin-blocked-header">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <span>Akses Login Admin Terblokir (5/5 Gagal)</span>
                        </div>
                        <div class="admin-blocked-desc">
                            Anda telah memasukkan kata sandi atau data login yang salah sebanyak <strong>5 kali</strong> berturut-turut. Demi keamanan sistem, akses masuk untuk <strong>Admin</strong> telah dikunci sementara.
                        </div>
                        <div class="admin-blocked-contact">
                            <span>📞 Silakan hubungi <strong>Master Admin</strong> untuk membuka blokir akun Anda.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <!-- Tambahkan name="email" -->
                        <input type="email" name="email" id="email-input" class="form-input" placeholder="nama@perusahaan.com" value="{{ old('email') }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="password-wrapper">
                            <!-- Tambahkan id="password-input" pada input ini -->
                            <input type="password" name="password" id="password-input" class="form-input" placeholder="••••••••" required>
                            
                            <!-- Tombol Ikon Mata -->
                            <button type="button" class="toggle-password-btn" id="toggle-password" tabindex="-1">
                                <!-- Ikon Mata Terbuka (Default) -->
                                <svg id="eye-icon" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <!-- Ikon Mata Tertutup (Disembunyikan awalnya) -->
                                <svg id="eye-off-icon" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                       <label class="ingat-saya">
    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Ingat saya
</label>
                        <div class="right-options">
                            
                            <a href="#" class="lupa-password">Lupa password?</a>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn" id="btn-submit">
                        <span id="btn-text">Masuk ke Akun</span>
                    </button>
                </form>

                <p class="halaman-ini-diperuntukkan">
                    Halaman ini diperuntukkan bagi Master Admin dengan hak akses penuh terhadap pengelolaan sistem inventaris.
                </p>
            </div>
        </div>
    </div>

    <script>
        const MAX_ADMIN_ATTEMPTS = 5;
        const STORAGE_KEY_COUNT = 'admin_login_failed_count';
        const STORAGE_KEY_BLOCKED = 'admin_login_is_blocked';
        const SESSION_KEY_LAST_ROLE = 'login_last_selected_role';
        const SESSION_KEY_PENDING_ADMIN = 'login_pending_admin_attempt';

        const SERVER_LOCK_INIT = @json($lockData);
        const ADA_ERROR_LOGIN_SERVER = @json($adaErrorLoginServer);
        const OLD_ROLE_SERVER = @json($oldRoleLogin);

        const btnAdmin = document.getElementById('btn-admin');
        const btnMaster = document.getElementById('btn-master');
        const hiddenRole = document.getElementById('login_as');
        const emailInput = document.getElementById('email-input');
        const passwordInput = document.getElementById('password-input');
        const formLogin = document.getElementById('form-login');
        const btnSubmit = document.getElementById('btn-submit');
        const btnText = document.getElementById('btn-text');
        const blockedBox = document.getElementById('admin-blocked-box');
        const warningBox = document.getElementById('admin-attempt-warning');
        const footerNote = document.querySelector('.halaman-ini-diperuntukkan');

        let serverLockState = SERVER_LOCK_INIT || { failed_count: 0, is_blocked: false };

        function getFailedCount() {
            const localCount = parseInt(localStorage.getItem(STORAGE_KEY_COUNT) || '0', 10) || 0;
            const srvCount = parseInt(serverLockState.failed_count || 0, 10) || 0;
            return Math.max(localCount, srvCount);
        }

        function isBlockedAdmin() {
            return localStorage.getItem(STORAGE_KEY_BLOCKED) === '1'
                || !!serverLockState.is_blocked
                || getFailedCount() >= MAX_ADMIN_ATTEMPTS;
        }

        function syncLockStateFromServerObj(lockObj) {
            if (!lockObj) return;
            serverLockState = lockObj;
            const srvCount = parseInt(lockObj.failed_count || 0, 10) || 0;
            localStorage.setItem(STORAGE_KEY_COUNT, String(srvCount));
            if (lockObj.is_blocked || srvCount >= MAX_ADMIN_ATTEMPTS) {
                localStorage.setItem(STORAGE_KEY_BLOCKED, '1');
            } else {
                localStorage.removeItem(STORAGE_KEY_BLOCKED);
            }
        }

        function perbaruiTampilanFormBerdasarkanRole() {
            const isRoleAdmin = (hiddenRole.value === 'admin');
            const count = getFailedCount();
            const terblokir = isBlockedAdmin();

            if (footerNote) {
                footerNote.textContent = isRoleAdmin
                    ? 'Halaman login khusus Admin. Maksimal 5 kali percobaan gagal sebelum akses dikunci otomatis.'
                    : 'Halaman ini diperuntukkan bagi Master Admin dengan hak akses penuh terhadap pengelolaan sistem inventaris.';
            }

            if (isRoleAdmin) {
                if (terblokir) {
                    blockedBox.classList.add('show');
                    warningBox.classList.remove('show');
                    emailInput.disabled = true;
                    passwordInput.disabled = true;
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('blocked-btn');
                    btnText.textContent = '🔒 Terblokir — Hubungi Master Admin';
                } else {
                    blockedBox.classList.remove('show');
                    emailInput.disabled = false;
                    passwordInput.disabled = false;
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('blocked-btn');
                    btnText.textContent = 'Masuk ke Akun';

                    if (count > 0 && count < MAX_ADMIN_ATTEMPTS) {
                        const sisa = MAX_ADMIN_ATTEMPTS - count;
                        warningBox.innerHTML = `⚠️ Kata sandi / data salah (<strong>${count}/${MAX_ADMIN_ATTEMPTS}</strong>). Sisa <strong>${sisa} percobaan</strong> lagi sebelum akses Admin diblokir.`;
                        warningBox.classList.add('show');
                    } else {
                        warningBox.classList.remove('show');
                    }
                }
            } else {
                // Mode Master Admin tetap bisa diakses agar Master Admin tidak ikut terkunci
                blockedBox.classList.remove('show');
                warningBox.classList.remove('show');
                emailInput.disabled = false;
                passwordInput.disabled = false;
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('blocked-btn');
                btnText.textContent = 'Masuk ke Akun';
            }
        }

        function pilihTabRole(roleValue) {
            if (roleValue === 'admin') {
                btnAdmin.classList.add('active');
                btnMaster.classList.remove('active');
                hiddenRole.value = 'admin';
            } else {
                btnMaster.classList.add('active');
                btnAdmin.classList.remove('active');
                hiddenRole.value = 'master_admin';
            }
            sessionStorage.setItem(SESSION_KEY_LAST_ROLE, hiddenRole.value);
            perbaruiTampilanFormBerdasarkanRole();
        }

        btnAdmin.addEventListener('click', function() {
            pilihTabRole('admin');
        });

        btnMaster.addEventListener('click', function() {
            pilihTabRole('master_admin');
        });

        // Cek apakah halaman baru saja reload karena gagal login pada tab Admin
        document.addEventListener('DOMContentLoaded', async function() {
            // Sinkronkan status awal dari server (jika Master Admin sudah membuka blokir, otomatis terbuka)
            if (!SERVER_LOCK_INIT.is_blocked && (SERVER_LOCK_INIT.failed_count || 0) === 0) {
                localStorage.removeItem(STORAGE_KEY_COUNT);
                localStorage.removeItem(STORAGE_KEY_BLOCKED);
            } else {
                syncLockStateFromServerObj(SERVER_LOCK_INIT);
            }

            const pendingAdminAttempt = sessionStorage.getItem(SESSION_KEY_PENDING_ADMIN) === '1';
            const savedRole = OLD_ROLE_SERVER || sessionStorage.getItem(SESSION_KEY_LAST_ROLE) || 'master_admin';

            if (ADA_ERROR_LOGIN_SERVER && (pendingAdminAttempt || savedRole === 'admin')) {
                sessionStorage.removeItem(SESSION_KEY_PENDING_ADMIN);

                // Tambah hitungan gagal login Admin
                const nextCount = getFailedCount() + 1;
                localStorage.setItem(STORAGE_KEY_COUNT, String(nextCount));
                if (nextCount >= MAX_ADMIN_ATTEMPTS) {
                    localStorage.setItem(STORAGE_KEY_BLOCKED, '1');
                }

                try {
                    const params = new URLSearchParams({
                        _login_lock_action: 'record_fail',
                        email: emailInput ? emailInput.value : ''
                    });
                    const res = await fetch(`{{ url()->current() }}?${params.toString()}`, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.lock) {
                            syncLockStateFromServerObj(data.lock);
                        }
                    }
                } catch (e) {}

                pilihTabRole('admin');
            } else {
                sessionStorage.removeItem(SESSION_KEY_PENDING_ADMIN);
                pilihTabRole(savedRole);
            }

            // Polling ringan setiap 3 detik: jika Master Admin membuka blokir dari halaman Roles & Permission,
            // form login Admin otomatis terbuka kembali secara real-time tanpa perlu refresh!
            setInterval(async () => {
                if (document.hidden) return;
                try {
                    const res = await fetch(`{{ url()->current() }}?_login_lock_action=status&_t=${Date.now()}`, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.lock) {
                            const wasBlocked = isBlockedAdmin();
                            syncLockStateFromServerObj(data.lock);
                            if (wasBlocked !== isBlockedAdmin()) {
                                perbaruiTampilanFormBerdasarkanRole();
                            }
                        }
                    }
                } catch (e) {}
            }, 3000);
        });

        const errorToast = document.getElementById('error-toast');
        if (errorToast) {
            setTimeout(() => {
                errorToast.classList.add('show');
            }, 100);

            setTimeout(() => {
                errorToast.classList.remove('show');
            }, 3500);
        }

        // --- PROTEKSI SUBMIT & ANIMASI LOADING BUTTON ---
        formLogin.addEventListener('submit', function(e) {
            if (hiddenRole.value === 'admin' && isBlockedAdmin()) {
                e.preventDefault();
                perbaruiTampilanFormBerdasarkanRole();
                return false;
            }

            if (hiddenRole.value === 'admin') {
                sessionStorage.setItem(SESSION_KEY_PENDING_ADMIN, '1');
                sessionStorage.setItem(SESSION_KEY_LAST_ROLE, 'admin');
            } else {
                sessionStorage.removeItem(SESSION_KEY_PENDING_ADMIN);
                sessionStorage.setItem(SESSION_KEY_LAST_ROLE, 'master_admin');
            }

            btnSubmit.disabled = true;
            btnText.innerHTML = '<span class="spinner"></span> Memproses...';
        });

        const togglePasswordBtn = document.getElementById('toggle-password');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeOffIcon = document.getElementById('eye-off-icon');

        togglePasswordBtn.addEventListener('click', function () {
            const currentType = passwordInput.getAttribute('type');
            const newType = currentType === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', newType);

            if (newType === 'text') {
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        });
    </script>
</body>
</html>