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

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <!-- Tambahkan name="email" -->
                        <input type="email" name="email" class="form-input" placeholder="nama@perusahaan.com" value="{{ old('email') }}" required>
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
        const btnAdmin = document.getElementById('btn-admin');
        const btnMaster = document.getElementById('btn-master');
        const hiddenRole = document.getElementById('login_as');

        btnAdmin.addEventListener('click', function() {
            btnAdmin.classList.add('active');
            btnMaster.classList.remove('active');
            hiddenRole.value = 'admin'; // Set nilai ke admin
        });

        btnMaster.addEventListener('click', function() {
            btnMaster.classList.add('active');
            btnAdmin.classList.remove('active');
            hiddenRole.value = 'master_admin'; // Set nilai ke master admin
        });

        const errorToast = document.getElementById('error-toast');
        if (errorToast) {
            // Munculkan notifikasi setelah 100ms
            setTimeout(() => {
                errorToast.classList.add('show');
            }, 100);

            // Hilangkan notifikasi setelah 3000ms (3 detik)
            setTimeout(() => {
                errorToast.classList.remove('show');
            }, 3000);
        }

        // --- SCRIPT ANIMASI LOADING BUTTON ---
        const formLogin = document.getElementById('form-login');
        const btnSubmit = document.getElementById('btn-submit');
        const btnText = document.getElementById('btn-text');

        formLogin.addEventListener('submit', function() {
            // Nonaktifkan tombol agar tidak bisa di-klik 2 kali
            btnSubmit.disabled = true;
            
            // Ubah teks tombol menjadi animasi spinner
            btnText.innerHTML = '<span class="spinner"></span> Memproses...';
        });

        const togglePasswordBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password-input');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeOffIcon = document.getElementById('eye-off-icon');

        togglePasswordBtn.addEventListener('click', function () {
            // Cek tipe input saat ini, lalu balik posisinya (toggle)
            const currentType = passwordInput.getAttribute('type');
            const newType = currentType === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', newType);

            // Ganti ikon mata sesuai dengan tipe input
            if (newType === 'text') {
                eyeIcon.style.display = 'none';       // Sembunyikan mata terbuka
                eyeOffIcon.style.display = 'block';   // Tampilkan mata tercoret
            } else {
                eyeIcon.style.display = 'block';      // Tampilkan mata terbuka
                eyeOffIcon.style.display = 'none';    // Sembunyikan mata tercoret
            }
        });
    </script>
</body>
</html>