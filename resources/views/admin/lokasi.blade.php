<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Lokasi - Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    
    <style>
        body.is-updating-global { cursor: wait !important; }
        body.is-updating-global .chip-wrapper { pointer-events: none !important; opacity: 0.7; }
        body.is-updating-global .chip-wrapper.is-editing { opacity: 1; }

        :root {
            --primary-color: #1a4f91;
            --secondary-color: #10b981;
            --danger-color: #ef4444;
            --bg-light: #f8fafc;
            --text-color: #000;
            --text-muted: rgba(0, 0, 0, 0.45);
            --border-color: rgba(0, 0, 0, 0.15);
            --sidebar-width: 270px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-light); display: flex; min-height: 100vh; overflow-x: hidden; }
        main { margin-left: var(--sidebar-width); padding: 25px; width: calc(100% - var(--sidebar-width)); display: flex; flex-direction: column; gap: 20px; }
        
        .main-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 15px; }
        .header-title h1 { font-size: 24px; font-weight: 700; color: var(--primary-color); }
        .header-title p { font-size: 13px; color: var(--text-muted); font-weight: 500; margin-top: 5px; }
        
        .btn { padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid var(--border-color); background: #fff; display: inline-flex; justify-content: center; align-items: center;}
        .btn-primary { background: var(--primary-color); color: #fff; border: none; }
        .btn:hover { opacity: 0.9; }

        .card-box { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid var(--border-color); }
        .section-title { font-size: 16px; font-weight: 700; margin-bottom: 15px; color: var(--text-color); border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;}

        .layout-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        @media (max-width: 900px) { .layout-grid { grid-template-columns: 1fr; } }

        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 15px; }
        .form-group label { font-size: 13px; font-weight: 600; }
        .form-group input { padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; outline: none; }

        .category-chips { display: flex; flex-direction: row; flex-wrap: wrap; gap: 10px; align-items: flex-start; }
        
        .chip-wrapper {
            display: flex; flex-direction: column;
            background: #f1f5f9; border: 1px solid #cbd5e1;
            border-radius: 12px; padding: 12px 16px;
            cursor: pointer; transition: all 0.2s ease; overflow: hidden;
            width: auto; min-width: 180px;
        }
        .chip-wrapper:hover { background: #e2e8f0; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .chip-wrapper.active { background: #fff; border-color: var(--primary-color); transform: translateY(0); box-shadow: 0 0 0 2px rgba(26, 79, 145, 0.2); }
        
        .chip-content { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .chip-name { color: #1e293b; font-size: 14px; font-weight: 700; }
        .chip-code { background: var(--primary-color); color: #fff; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; letter-spacing: 0.5px; }

        .chip-wrapper.is-editing .chip-content { display: none; }
        .chip-wrapper.is-editing .chip-actions { display: none !important; }
        
        .chip-edit-form { display: none; flex-direction: column; gap: 8px; width: 100%; }
        .chip-wrapper.is-editing .chip-edit-form { display: flex; }
        .chip-edit-form .edit-input-group { display: flex; align-items: center; gap: 8px; width: 100%; }
        .chip-edit-form input { border: 1px solid #94a3b8; border-radius: 6px; padding: 6px 10px; font-size: 13px; color: #000; width: 100%; outline: none; }
        .chip-edit-form input[name="kode_lokasi"] { width: 70px; text-align: center; font-weight: 700; background: #f8fafc; text-transform: uppercase; }

        .btn-update-sm { background: var(--secondary-color); color: #fff; border: none; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn-cancel-sm { background: #e2e8f0; color: #1e293b; border: none; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn-edit-sm { background: var(--primary-color); color: #fff; border: none; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn-delete-sm { background: #fee2e2; color: var(--danger-color); border: none; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 700; cursor: pointer; }

        .chip-actions { display: none; justify-content: flex-end; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px dashed #cbd5e1; width: 100%; animation: slideDown 0.2s ease; }
        .chip-wrapper.active .chip-actions { display: flex; }

        @keyframes slideDown { from { opacity: 0; margin-top: 0; padding-top: 0; max-height: 0; } to { opacity: 1; margin-top: 12px; padding-top: 12px; max-height: 50px; } }

        .spinner-sm { display: inline-block; width: 12px; height: 12px; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 50%; border-top-color: #fff; animation: spin-btn 1s ease-in-out infinite; margin-right: 4px; vertical-align: middle; }
        @keyframes spin-btn { to { transform: rotate(360deg); } }
        .btn-loading { opacity: 0.7; cursor: not-allowed !important; pointer-events: none; }

        .toast-popup { position: fixed; top: 30px; right: -100%; padding: 15px 20px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); font-size: 14px; font-weight: 600; z-index: 9999; display: flex; align-items: center; gap: 12px; transition: right 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        .toast-popup.show { right: 30px; }
        .toast-success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .toast-error { background-color: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>

    @include('layouts.adminsidebar')

    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>Master Lokasi Ruangan</h1>
                <p>Pusat pengelolaan daftar ruangan dan gedung kantor untuk penempatan fisik aset.</p>
            </div>
        </header>

        @if(session('success'))
            <div id="toast-success" class="toast-popup toast-success show"><span>{{ session('success') }}</span></div>
        @endif
        @if($errors->any())
            <div id="toast-error" class="toast-popup toast-error show"><span>{{ $errors->first() }}</span></div>
        @endif

        <div class="layout-grid">
            <!-- Form Tambah Lokasi -->
            <div class="card-box" style="align-self: start; border-top: 4px solid var(--primary-color);">
                <h2 class="section-title">Tambah Lokasi Baru</h2>
                <form action="{{ route('lokasi.store') }}" method="POST" id="form-lokasi">
                    @csrf
                    <div class="form-group">
                        <label>Nama Ruangan / Lokasi</label>
                        <input type="text" id="nama_lokasi" name="nama_lokasi" placeholder="Contoh: RUANG FINANCE" required style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Kode / Singkatan</label>
                        <input type="text" id="kode_lokasi" name="kode_lokasi" placeholder="Contoh: FIN" required style="text-transform: uppercase;">
                    </div>
                    <button type="submit" class="btn btn-primary" id="btn-simpan-lokasi" style="width: 100%; margin-top: 10px;">
                        <span id="text-simpan-lokasi">Simpan Lokasi</span>
                    </button>
                </form>
            </div>

            <!-- Daftar Lokasi Tersimpan -->
            <div class="card-box">
                <h2 class="section-title">Daftar Lokasi Tersimpan</h2>
                <div class="category-chips">
                    @forelse($lokasis as $lok)
                        <div class="chip-wrapper" id="chip-wrapper-lok-{{ $lok->id }}" onclick="toggleChipActions(this)">
                            <div class="chip-content">
                                <span class="chip-name">{{ $lok->nama_lokasi }}</span>
                                <span class="chip-code">{{ $lok->kode_lokasi }}</span>
                            </div>
                            
                            <!-- Form Edit Inline -->
                            <form action="{{ url('/admin/master-lokasi/'.$lok->id) }}" method="POST" class="chip-edit-form" id="form-edit-lok-{{ $lok->id }}" onsubmit="setLoadingUpdate(event, 'lok-{{ $lok->id }}')" onclick="event.stopPropagation()">
                                @csrf
                                @method('PUT')
                                <div class="edit-input-group">
                                    <input type="text" name="nama_lokasi" value="{{ $lok->nama_lokasi }}" required style="text-transform: uppercase;">
                                    <input type="text" name="kode_lokasi" value="{{ $lok->kode_lokasi }}" required style="text-transform: uppercase;">
                                </div>
                                <div style="display: flex; gap: 6px; justify-content: flex-end; margin-top: 6px;">
                                    <button type="button" class="btn-cancel-sm" onclick="cancelEdit('lok-{{ $lok->id }}')">Batal</button>
                                    <button type="submit" class="btn-update-sm" id="btn-update-lok-{{ $lok->id }}">Update</button>
                                </div>
                            </form>

                            <div class="chip-actions" onclick="event.stopPropagation()">
                                <button type="button" class="btn-edit-sm" onclick="enableInlineEdit('lok-{{ $lok->id }}')">Edit</button>
                                <form action="{{ url('/admin/master-lokasi/'.$lok->id) }}" method="POST" class="form-delete-lokasi" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete-sm">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p style="font-size: 13px; color: var(--text-muted);">Belum ada data lokasi yang disimpan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => { document.querySelectorAll('.toast-popup').forEach(el => el.classList.remove('show')); }, 2500);
        });

        function showCustomToast(message, type) {
            let toast = document.createElement('div');
            toast.className = `toast-popup toast-${type === 'success' ? 'success' : 'error'} show`;
            toast.innerHTML = `<span>${message}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 500); }, 2500);
        }

        function toggleChipActions(element) {
            document.querySelectorAll('.chip-wrapper').forEach(el => {
                if (el !== element) el.classList.remove('active');
            });
            element.classList.toggle('active');
        }

        document.addEventListener('click', function(e) {
            if(!e.target.closest('.chip-wrapper')) {
                document.querySelectorAll('.chip-wrapper').forEach(el => el.classList.remove('active'));
            }
        });

        function enableInlineEdit(id) {
            document.querySelectorAll('.chip-wrapper').forEach(el => el.classList.remove('is-editing'));
            document.getElementById('chip-wrapper-' + id).classList.add('is-editing');
        }

        function cancelEdit(id) {
            const wrapper = document.getElementById('chip-wrapper-' + id);
            if (!wrapper) return;
            wrapper.classList.remove('is-editing', 'active');
        }

        function setLoadingUpdate(event, id) {
            const btnUpdate = document.getElementById('btn-update-' + id);
            document.body.classList.add('is-updating-global');
            btnUpdate.classList.add('btn-loading');
            btnUpdate.innerHTML = '<span class="spinner-sm"></span>...';
        }

        // AJAX Tambah Lokasi
        document.getElementById('form-lokasi').addEventListener('submit', function(e) {
            e.preventDefault();
            let btnSubmit = document.getElementById('btn-simpan-lokasi');
            btnSubmit.classList.add('btn-loading');
            btnSubmit.innerHTML = '<span class="spinner-sm"></span> Menyimpan...';

            fetch(this.action, {
                method: 'POST', body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(res => {
                if (res.status === 200 && res.body.success) {
                    showCustomToast(res.body.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showCustomToast('Gagal menyimpan lokasi.', 'error');
                    btnSubmit.classList.remove('btn-loading'); btnSubmit.innerHTML = 'Simpan Lokasi';
                }
            }).catch(err => { showCustomToast('Terjadi kesalahan.', 'error'); });
        });

        // AJAX Update Lokasi
        document.querySelectorAll('.chip-edit-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                let fullId = this.id.replace('form-edit-', ''); 
                fetch(this.action, {
                    method: 'POST', body: new FormData(this),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                }).then(res => res.json().then(data => ({ status: res.status, body: data })))
                .then(res => {
                    document.body.classList.remove('is-updating-global');
                    if (res.status === 200 && res.body.success) {
                        showCustomToast(res.body.message, 'success');
                        setTimeout(() => location.reload(), 500);
                    } else { showCustomToast('Gagal update.', 'error'); }
                }).catch(err => { document.body.classList.remove('is-updating-global'); });
            });
        });

        // AJAX Delete Lokasi
        document.querySelectorAll('.form-delete-lokasi').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!confirm('Yakin ingin menghapus lokasi ini?')) return;
                let chipWrapper = this.closest('.chip-wrapper');
                document.body.classList.add('is-updating-global');

                fetch(this.action, {
                    method: 'POST', body: new FormData(this),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                }).then(res => res.json().then(data => ({ status: res.status, body: data })))
                .then(res => {
                    document.body.classList.remove('is-updating-global');
                    if (res.status === 200 && res.body.success) {
                        showCustomToast(res.body.message, 'success');
                        chipWrapper.style.transform = 'scale(0.8)'; chipWrapper.style.opacity = '0';
                        setTimeout(() => chipWrapper.remove(), 300);
                    } else { showCustomToast('Gagal menghapus.', 'error'); }
                }).catch(err => { document.body.classList.remove('is-updating-global'); });
            });
        });
    </script>
</body>
</html>