<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Akun - Master Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Coda+Caption:wght@800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />

    <style>
        body.is-updating-global {
            cursor: wait !important;
        }

        body.is-updating-global .chip-wrapper {
            pointer-events: none !important;
            opacity: 0.7;
        }

        body.is-updating-global .chip-wrapper.is-editing {
            opacity: 1;
        }

        :root {
            --primary-color: #1a4f91;
            --secondary-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --bg-light: #f8fafc;
            --text-color: #000;
            --text-muted: rgba(0, 0, 0, 0.45);
            --border-color: rgba(0, 0, 0, 0.15);
            --sidebar-width: 270px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        main {
            margin-left: var(--sidebar-width);
            padding: 25px;
            width: calc(100% - var(--sidebar-width));
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }

        .header-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .header-title p {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 5px;
        }

        .btn {
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: #fff;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .btn-primary {
            background: var(--primary-color);
            color: #fff;
            border: none;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .card-box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--text-color);
            border-bottom: 1px dashed var(--border-color);
            padding-bottom: 10px;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }

        @media (max-width: 900px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            outline: none;
        }

        .category-chips {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }

        .chip-wrapper {
            display: flex;
            flex-direction: column;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .chip-wrapper:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .chip-wrapper.active {
            background: #fff;
            border-color: var(--primary-color);
            transform: translateY(0);
            box-shadow: 0 0 0 2px rgba(26, 79, 145, 0.2);
        }

        .chip-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .chip-name {
            color: #1e293b;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .chip-code {
            background: var(--primary-color);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 6px;
            letter-spacing: 0.5px;
        }

        .chip-wrapper.is-editing .chip-content {
            display: none;
        }

        .chip-wrapper.is-editing .chip-actions {
            display: none !important;
        }

        .chip-edit-form {
            display: none;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .chip-wrapper.is-editing .chip-edit-form {
            display: flex;
        }

        .chip-edit-form .edit-input-group {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
        }

        .chip-edit-form input,
        .chip-edit-form select {
            border: 1px solid #94a3b8;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            color: #000;
            width: 100%;
            outline: none;
        }

        .chip-edit-form input[name="kode_kategori"] {
            width: 70px;
            text-align: center;
            font-weight: 700;
            background: #f8fafc;
        }

        .btn-update-sm {
            background: var(--secondary-color);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-cancel-sm {
            background: #e2e8f0;
            color: #1e293b;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-edit-sm {
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-delete-sm {
            background: #fee2e2;
            color: var(--danger-color);
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .chip-actions {
            display: none;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #cbd5e1;
            width: 100%;
            animation: slideDown 0.2s ease;
        }

        .chip-wrapper.active .chip-actions {
            display: flex;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                margin-top: 0;
                padding-top: 0;
                max-height: 0;
            }

            to {
                opacity: 1;
                margin-top: 12px;
                padding-top: 12px;
                max-height: 50px;
            }
        }

        .spinner-btn {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin-btn 1s ease-in-out infinite;
            margin-right: 6px;
            vertical-align: middle;
        }

        .spinner-sm {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin-btn 1s ease-in-out infinite;
            margin-right: 4px;
            vertical-align: middle;
        }

        @keyframes spin-btn {
            to {
                transform: rotate(360deg);
            }
        }

        .btn-loading {
            opacity: 0.7;
            cursor: not-allowed !important;
            pointer-events: none;
        }

        .toast-popup {
            position: fixed;
            top: 30px;
            right: -100%;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            font-size: 14px;
            font-weight: 600;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: right 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .toast-popup.show {
            right: 30px;
        }

        .toast-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .toast-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        /* ===== KELOLA AKUN ===== */
        .role-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; align-items: start; }
        @media (max-width: 1280px) { .role-grid { grid-template-columns: 1fr; } }
        .role-col { display: flex; flex-direction: column; gap: 20px; min-width: 0; }
        .role-count { display: inline-block; margin-left: 8px; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #fff; background: var(--role-color); vertical-align: middle; }
        .password-wrap { position: relative; }
        .password-wrap input { width: 100%; padding-right: 74px; }
        .pw-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none; color: var(--primary-color); font-size: 12px; font-weight: 600; cursor: pointer; }
        .akun-list { display: flex; flex-direction: column; gap: 10px; }
        .akun-item { border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; background: #fff; }
        .akun-view { display: flex; align-items: center; gap: 12px; }
        .akun-avatar { width: 38px; height: 38px; border-radius: 50%; background: var(--role-color); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
        .akun-info { flex: 1; min-width: 0; }
        .akun-nama { font-weight: 600; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .akun-email { font-size: 12px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .akun-tag { margin-left: 6px; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; background: #e0f2fe; color: #0369a1; }
        .akun-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .akun-actions form { margin: 0; }
        .btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        .btn-sm-edit { background: rgba(26, 79, 145, 0.1); color: var(--primary-color); }
        .btn-sm-hapus { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .btn-sm-batal { background: #e2e8f0; color: #334155; }
        .akun-edit { display: none; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border-color); }
        .akun-item.is-editing .akun-edit { display: block; }
        .akun-edit-aksi { display: flex; gap: 8px; justify-content: flex-end; margin-top: 4px; }
        .akun-kosong { font-size: 13px; color: var(--text-muted); }
        .akun-catatan { font-size: 12px; color: var(--text-muted); margin-top: 14px; }
    </style>
</head>

<body>

    @include('layouts.mastersidebar')

    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>Kelola Akun</h1>
                <p>Tambah, ubah, dan hapus akun Master Admin, Admin, dan User. Semua aktivitas tercatat di Riwayat Aktivitas.</p>
            </div>
        </header>

        @if(session('success'))
        <div id="toast-success" class="toast-popup toast-success show">
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div id="toast-error" class="toast-popup toast-error show">
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        <div class="role-grid">
            @foreach($roles as $kodeRole => $labelRole)
            @php
                $daftar    = $akuns[$kodeRole] ?? collect();
                $warna     = ['master_admin' => '#1a4f91', 'admin' => '#10b981', 'user' => '#f59e0b'][$kodeRole] ?? '#1a4f91';
                $bolehHapus = $kodeRole !== 'master_admin';
                $isiLama   = old('role') === $kodeRole;
            @endphp
            <section class="role-col" style="--role-color: {{ $warna }};">

                <!-- Form tambah akun -->
                <div class="card-box" style="border-top: 4px solid var(--role-color);">
                    <h2 class="section-title">Tambah {{ $labelRole }}</h2>
                    <form action="{{ route('kelola_akun.store') }}" method="POST" autocomplete="off">
                        @csrf
                        <input type="hidden" name="role" value="{{ $kodeRole }}">

                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" value="{{ $isiLama ? old('nama_lengkap') : '' }}" maxlength="100" placeholder="Nama lengkap" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ $isiLama ? old('email') : '' }}" maxlength="150" placeholder="nama@perusahaan.com" autocomplete="off" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="password-wrap">
                                <input type="password" name="password" minlength="8" maxlength="100" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                                <button type="button" class="pw-toggle" onclick="togglePassword(this)">Lihat</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 6px; background: var(--role-color);">
                            Tambah {{ $labelRole }}
                        </button>
                    </form>
                </div>

                <!-- Daftar akun -->
                <div class="card-box">
                    <h2 class="section-title">Daftar {{ $labelRole }} <span class="role-count">{{ $daftar->count() }}</span></h2>

                    <div class="akun-list">
                        @forelse($daftar as $akun)
                        @php
                            $idAkun     = $akun->getKey();
                            $akunSaya   = (int) $idAkun === (int) $idSaya;
                            $sedangEdit = $errors->any() && (string) old('_edit_id') === (string) $idAkun;
                        @endphp
                        <div class="akun-item {{ $sedangEdit ? 'is-editing' : '' }}" id="akun-{{ $idAkun }}">
                            <div class="akun-view">
                                <div class="akun-avatar">{{ strtoupper(mb_substr($akun->nama_lengkap ?? '?', 0, 1)) }}</div>
                                <div class="akun-info">
                                    <div class="akun-nama">{{ $akun->nama_lengkap }}@if($akunSaya)<span class="akun-tag">Anda</span>@endif</div>
                                    <div class="akun-email">{{ $akun->email }}</div>
                                </div>
                                <div class="akun-actions">
                                    <button type="button" class="btn-sm btn-sm-edit" onclick="toggleEdit('akun-{{ $idAkun }}')">Edit</button>
                                    @if($bolehHapus && !$akunSaya)
                                    <form action="{{ route('kelola_akun.destroy', $idAkun) }}" method="POST" data-nama="{{ $akun->nama_lengkap }}" data-role="{{ $labelRole }}" onsubmit="return konfirmasiHapus(this)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-sm btn-sm-hapus">Hapus</button>
                                    </form>
                                    @endif
                                </div>
                            </div>

                            <!-- Form edit (muncul saat tombol Edit diklik) -->
                            <form class="akun-edit" action="{{ route('kelola_akun.update', $idAkun) }}" method="POST" autocomplete="off">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_edit_id" value="{{ $idAkun }}">

                                <div class="form-group">
                                    <label>Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" value="{{ $sedangEdit ? old('nama_lengkap') : $akun->nama_lengkap }}" maxlength="100" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" value="{{ $sedangEdit ? old('email') : $akun->email }}" maxlength="150" autocomplete="off" required>
                                </div>
                                <div class="form-group">
                                    <label>Password Baru <span style="font-weight: 400; color: var(--text-muted);">(kosongkan jika tidak diganti)</span></label>
                                    <div class="password-wrap">
                                        <input type="password" name="password" minlength="8" maxlength="100" placeholder="Minimal 8 karakter" autocomplete="new-password">
                                        <button type="button" class="pw-toggle" onclick="togglePassword(this)">Lihat</button>
                                    </div>
                                </div>
                                <div class="akun-edit-aksi">
                                    <button type="button" class="btn-sm btn-sm-batal" onclick="toggleEdit('akun-{{ $idAkun }}')">Batal</button>
                                    <button type="submit" class="btn-sm" style="background: var(--role-color); color: #fff;">Simpan</button>
                                </div>
                            </form>
                        </div>
                        @empty
                        <p class="akun-kosong">Belum ada akun {{ $labelRole }}.</p>
                        @endforelse
                    </div>

                    @unless($bolehHapus)
                    <p class="akun-catatan">Akun Master Admin dapat diubah, tetapi tidak dapat dihapus.</p>
                    @endunless
                </div>

            </section>
            @endforeach
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(() => {
                document.querySelectorAll('.toast-popup').forEach(el => el.classList.remove('show'));
            }, 3500);
        });

        function toggleEdit(id) {
            document.getElementById(id).classList.toggle('is-editing');
        }

        function togglePassword(tombol) {
            const input = tombol.parentElement.querySelector('input');
            const tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            tombol.textContent = tampil ? 'Sembunyi' : 'Lihat';
        }

        function konfirmasiHapus(form) {
            return confirm('Hapus akun ' + form.dataset.role + ' "' + form.dataset.nama + '"?\nAkun ini tidak akan bisa login lagi.');
        }
    </script>
</body>

</html>