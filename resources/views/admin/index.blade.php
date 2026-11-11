<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin</title>
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
            height: 100vh;
            overflow: hidden;
        }

        .login-master-admin {
            display: flex;
            width: 100%;
            height: 100%;
        }

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
    right: -100%;
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
    transition: right 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.toast-notification.show {
    right: 25px;
}

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
    padding-right: 40px;
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

        @media screen and (max-width: 968px) {
            body {
                background: linear-gradient(155deg, #0d2642 0%, #102a45 55%, #0a1e36 100%);
                overflow-y: auto;
                min-height: 100vh;
            }

            .login-master-admin {
                flex-direction: column;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 16px 12px 24px;
                gap: 12px;
            }

            .info-panel {
                display: flex;
                width: 100%;
                max-width: 356px;
                padding: 0;
                margin-bottom: 2px;
            }

            .info-panel .hero-section,
            .info-panel .features-container {
                display: none;
            }

            .brand-header {
                margin-bottom: 0;
                width: 100%;
                justify-content: center;
                text-align: center;
                padding: 8px 12px;
                background: rgba(255, 255, 255, 0.06);
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 11px;
            }

            .brand-text {
                align-items: center;
            }

            .pt-name {
                font-size: 13.5px;
            }

            .logistics-solutions {
                font-size: 9px;
                letter-spacing: 0.8px;
            }

            .login-panel {
                width: 100%;
                min-height: auto;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .login-card {
                max-width: 356px;
                padding: 1.25rem 1.3rem;
                border-radius: 14px;
                box-shadow: 0 14px 32px rgba(0, 0, 0, 0.35);
            }

            .login-header {
                margin-bottom: 0.9rem;
            }

            .selamat-datang-kembali {
                font-size: 17.5px;
                margin-bottom: 0.2rem;
            }

            .masuk-ke-akun-desc {
                font-size: 11.5px;
            }

            .role-toggle {
                padding: 3px;
                margin-bottom: 0.95rem;
                border-radius: 9px;
            }

            .role-btn {
                padding: 0.48rem;
                font-size: 12px;
                border-radius: 7px;
            }

            .form-group {
                margin-bottom: 0.78rem;
            }

            .form-label {
                font-size: 11.5px;
                margin-bottom: 0.35rem;
            }

            .form-input {
                padding: 0.62rem 0.82rem;
                font-size: 12.5px;
                border-radius: 9px;
            }

            .form-options {
                margin-bottom: 1rem;
                font-size: 11.5px;
            }

            .submit-btn {
                padding: 0.7rem;
                font-size: 13.5px;
                border-radius: 9px;
                margin-bottom: 0.85rem;
            }

            .halaman-ini-diperuntukkan {
                font-size: 10.2px;
                line-height: 1.35;
            }

            .toast-notification {
                top: 12px;
                left: 12px;
                right: 12px !important;
                padding: 11px 14px;
                font-size: 12px;
                border-radius: 10px;
                transform: translateY(-130%);
                transition: transform 0.35s ease;
            }

            .toast-notification.show {
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="login-master-admin">

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

        <div class="login-panel">
            <div class="login-card">
                <div class="login-header">
                    <h2 class="selamat-datang-kembali">Selamat Datang Kembali</h2>
                    <p class="masuk-ke-akun-desc">Masuk ke akun Anda untuk mengelola pengiriman kargo</p>
                </div>

                <div class="role-toggle">
                    <button type="button" class="role-btn" id="btn-admin">Admin</button>
                    <button type="button" class="role-btn active" id="btn-master">Master Admin</button>
                </div>

                <form class="login-form" action="{{ route('login.proses') }}" method="POST" id="form-login">
                    @csrf
                    <input type="hidden" name="login_as" id="login_as" value="master_admin">

                    @if ($errors->any())
                        <div id="error-toast" class="toast-notification">

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

                        <input type="email" name="email" class="form-input" placeholder="nama@perusahaan.com" value="{{ old('email') }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="password-wrapper">

                            <input type="password" name="password" id="password-input" class="form-input" placeholder="••••••••" required>

                            <button type="button" class="toggle-password-btn" id="toggle-password" tabindex="-1">

                                <svg id="eye-icon" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>

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
            hiddenRole.value = 'admin';
        });

        btnMaster.addEventListener('click', function() {
            btnMaster.classList.add('active');
            btnAdmin.classList.remove('active');
            hiddenRole.value = 'master_admin';
        });

        const errorToast = document.getElementById('error-toast');
        if (errorToast) {

            setTimeout(() => {
                errorToast.classList.add('show');
            }, 100);

            setTimeout(() => {
                errorToast.classList.remove('show');
            }, 3000);
        }

        const formLogin = document.getElementById('form-login');
        const btnSubmit = document.getElementById('btn-submit');
        const btnText = document.getElementById('btn-text');

        formLogin.addEventListener('submit', function() {

            btnSubmit.disabled = true;

            btnText.innerHTML = '<span class="spinner"></span> Memproses...';
        });

        const togglePasswordBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password-input');
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
