# Sistem Informasi Inventaris PT DMK (`inventaris-dmk`)

Aplikasi manajemen inventaris aset PT DMK berbasis **Laravel 13** (`PHP ^8.3`) yang mencakup portal **Master Admin**, portal **Admin Operasional**, **Tracking Pengadaan**, **Live Auto-Sync** (pembaruan data real-time tanpa refresh manual), **Developer Console**, serta **REST API Mobile** dengan proteksi keamanan berlapis.

---

## Prasyarat Sistem

Pastikan perangkat Anda sudah terpasang komponen berikut sebelum melakukan *cloning*:

- **Git**
- **PHP `^8.3`** dengan ekstensi aktif di `php.ini`:
  - `pdo_mysql` (dan `pdo_sqlite` jika memakai SQLite)
  - `mbstring`
  - `openssl`
  - `fileinfo` (validasi MIME upload lampiran)
  - `gd` (pengolahan gambar & QR Code)
  - `zip` (import/export Excel via `maatwebsite/excel`)
- **Composer** (versi 2.x)
- **MySQL / MariaDB** (bisa memakai **Laragon** atau **XAMPP**)
- *(Opsional)* **Node.js & npm** (hanya jika ingin mengubah asset Vite di `resources/css/` atau `resources/js/`)

Verifikasi di terminal sebelum memulai:

```bash
git --version
php -v
composer -V
```

---

## 1. Tata Cara Cloning Project dari GitHub

Repository ini tersedia di:
`https://github.com/satriamikaanjay/inventaris-dmk.git`

Branch utama web yang sudah disinkronkan penuh:
- **`Satria-(masteradmin)`** — Versi lengkap terbaru (Master Admin, Admin, Tracking Pengadaan, Live Auto-Sync, Developer Console, API Mobile).
- **`Naila-(Admin)`** — Berisi project terbaru yang identik/tersinkron penuh dengan `Satria-(masteradmin)`.

> **Penting:** Nama branch mengandung tanda kurung `()`. Selalu apit nama branch dengan tanda kutip `"..."` di CMD, PowerShell, maupun Git Bash agar tidak dianggap syntax error oleh shell.

### Opsi A — Clone Baru dari Awal

1. Buka terminal lalu pindah ke direktori web server Anda (misalnya `C:\laragon\www` jika memakai Laragon atau `C:\xampp\htdocs` jika memakai XAMPP):
   ```bash
   cd C:\laragon\www
   ```
2. Clone branch yang diinginkan:
   - **Mengambil dari branch `Satria-(masteradmin)`:**
     ```bash
     git clone -b "Satria-(masteradmin)" https://github.com/satriamikaanjay/inventaris-dmk.git INVENTARIS-DMK
     ```
   - **Atau dari branch `Naila-(Admin)`:**
     ```bash
     git clone -b "Naila-(Admin)" https://github.com/satriamikaanjay/inventaris-dmk.git INVENTARIS-DMK
     ```
3. Masuk ke folder project:
   ```bash
   cd INVENTARIS-DMK
   ```

### Opsi B — Jika Folder Sudah Pernah Di-clone Sebelumnya (Menyamakan dengan GitHub)

Jika Anda sudah memiliki folder repo lama dan ingin memperbarui/menimpa isinya agar sama persis dengan versi terbaru di GitHub:

```bash
cd C:\laragon\www\INVENTARIS-DMK
git fetch origin
git checkout "Satria-(masteradmin)"
git reset --hard "origin/Satria-(masteradmin)"
```
*(Ganti `"Satria-(masteradmin)"` menjadi `"Naila-(Admin)"` jika bekerja pada branch Naila.)*

---

## 2. Install Dependensi Laravel (`vendor/`)

Folder `vendor/` tidak ikut di-upload ke GitHub, sehingga wajib diinstall setelah clone:

```bash
composer install
```

*(Opsional, hanya jika Anda memodifikasi frontend Vite CSS/JS)*:
```bash
npm install
npm run build
```

---

## 3. Membuat & Mengatur File `.env` (Penjelasan Key & Pengaturan DB)

File `.env` menyimpan konfigurasi sensitif dan pengaturan spesifik komputer/server lokal Anda (tidak di-commit ke GitHub).

### Langkah 3.1 — Salin `.env.example` Menjadi `.env`

- **Windows CMD:**
  ```cmd
  copy .env.example .env
  ```
- **PowerShell / Git Bash / Linux / macOS:**
  ```bash
  cp .env.example .env
  ```

### Langkah 3.2 — Generate `APP_KEY` (Wajib)

Jalankan perintah berikut agar Laravel otomatis mengisi key enkripsi `APP_KEY=base64:...` di dalam file `.env`:

```bash
php artisan key:generate
```

> Jika langkah ini dilewati, aplikasi akan memunculkan error `MissingAppKeyException: No application encryption key has been specified`.

### Langkah 3.3 — Daftar Key Penting di `.env` yang Perlu Diatur

Buka file **`.env`** di root folder project, lalu cek dan sesuaikan key berikut:

#### A. Pengaturan Aplikasi (`APP_*`)
```env
APP_NAME="Inventaris DMK"
APP_ENV=local
APP_KEY=base64:...          # Terisi otomatis oleh `php artisan key:generate`
APP_DEBUG=false             # Ubah ke `true` jika butuh melihat detail error saat dev lokal; WAJIB `false` di production/publik
APP_URL=http://127.0.0.1:8000
```
- **Cara mengganti URL aplikasi (`APP_URL`):**
  - Jika menjalankan pakai `php artisan serve`: gunakan `APP_URL=http://127.0.0.1:8000`.
  - Jika memakai Virtual Host Laragon: ubah ke `APP_URL=http://inventaris-dmk.test` (atau nama domain Anda).
  - Pengaturan ini dipakai Laravel saat membuat link QR Code aset, URL asset, dan endpoint internal.

#### B. Pengaturan Database & Cara Mengganti Nama Database (`DB_*`)
Pengaturan koneksi database berada di file **`.env`** (dibaca oleh [`config/database.php`](config/database.php)). Untuk **MySQL / MariaDB (Laragon / XAMPP)**, atur bagian ini:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventaris_dmk
DB_USERNAME=root
DB_PASSWORD=
```

Penjelasan masing-masing key database:
- **`DB_CONNECTION`**: Gunakan `mysql` untuk MySQL/MariaDB. *(Hanya ubah ke `sqlite` jika ingin pengujian memakai file `database/database.sqlite`.)*
- **`DB_HOST`**: Alamat server MySQL (`127.0.0.1` untuk lokal).
- **`DB_PORT`**: Port MySQL (default `3306`, cek Laragon/XAMPP jika Anda mengubah port MySQL).
- **`DB_DATABASE`**: **Nama database MySQL yang dipakai aplikasi.** Jika di phpMyAdmin/HeidiSQL Anda memberi nama database lain (misalnya `db_aset_dmk`), cukup ganti baris ini menjadi `DB_DATABASE=db_aset_dmk`.
- **`DB_USERNAME`**: Username MySQL (default Laragon/XAMPP: `root`).
- **`DB_PASSWORD`**: Password MySQL (biarkan kosong setelah `=` jika MySQL lokal tidak memakai password).

#### C. Key Keamanan, Developer Console, dan CAPTCHA
Didefinisikan di [`.env`](.env.example) dan dibaca melalui [`config/services.php`](config/services.php):

```env
DEV_ACCESS_KEY=DMK-DEV-2026-KEY
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
```

| Key di `.env` | Fungsi | Pengaturan yang Disarankan |
| :--- | :--- | :--- |
| **`DEV_ACCESS_KEY`** | Kunci akses tambahan pada halaman `/dev-console/login` (bersama username & password akun ber-role `developer`). | Default fallback di [`config/services.php`](config/services.php) adalah `DMK-DEV-2026-KEY`. **Wajib diganti** dengan kode rahasia Anda sendiri saat deploy ke server online/production. |
| **`TURNSTILE_SITE_KEY`** & **`TURNSTILE_SECRET_KEY`** | Kunci **Cloudflare Turnstile** (CAPTCHA pada form login web & Developer Console). | **Kosongkan** keduanya saat development lokal jika tidak memakai Turnstile. Jika web sudah online memakai domain & Cloudflare Turnstile, isi kedua key dari dashboard Cloudflare. |

> **Penting:** Setiap kali Anda mengubah nilai di `.env` (terutama `DB_DATABASE`, `APP_URL`, `DEV_ACCESS_KEY`, atau `TURNSTILE_*`), jalankan `php artisan config:clear` agar Laravel langsung memuat nilai terbaru.

---

## 4. Setup Database

Pastikan service **MySQL / MariaDB** di Laragon atau XAMPP sudah menyala.

1. Buka **phpMyAdmin**, **HeidiSQL**, atau terminal MySQL.
2. Buat database baru kosong dengan nama yang **sama persis** dengan `DB_DATABASE` di `.env` (contoh: `inventaris_dmk`).

Lalu pilih salah satu metode pengisian tabel di bawah ini:

### Metode A — Import File Backup `.sql` (Direkomendasikan jika memiliki dump database proyek)

1. Import file `.sql` ke dalam database yang baru Anda buat (`inventaris_dmk`).
2. Jalankan migrasi Laravel untuk memastikan struktur/data terbaru (termasuk sinkronisasi kolom `unit` per item–kategori–cabang dan penyelarasan `Baik` vs tipe aset) sudah diterapkan:
   ```bash
   php artisan migrate
   ```

### Metode B — Setup Database Kosong dari Migrasi Laravel

1. Jalankan seluruh file migrasi:
   ```bash
   php artisan migrate
   ```
2. Buat data role dasar dan akun Master Admin awal:
   ```bash
   php artisan db:seed --class=RoleSeeder
   php artisan db:seed --class=MasterAdminSeeder
   ```
   Akun Master Admin bawaan dari [`MasterAdminSeeder.php`](database/seeders/MasterAdminSeeder.php):
   - **Email:** `masteradmin@gmail.com`
   - **Password:** `password123`

   *(Tersedia juga seeder [`UserSeeder.php`](database/seeders/UserSeeder.php) untuk akun `master@admin.com` / `password123`. Segera ubah password bawaan setelah login pertama lewat menu Kelola Akun. Catatan: [`MasterDataSeeder.php`](database/seeders/MasterDataSeeder.php) masih mengacu pada model `Lokasi` yang opsional; tabel `pengajuan_asets` akan dibuat otomatis oleh [`AdminPengajuanController.php`](app/Http/Controllers/AdminPengajuanController.php) saat menu Pengajuan diakses.)*

---

## 5. Setup Storage Link & Permission Folder

Hubungkan `public/storage` ke `storage/app/public` agar gambar QR Code aset (`storage/app/public/qrcodes/`) dan lampiran file aset dapat diakses browser:

```bash
php artisan storage:link
```

Pastikan direktori berikut memiliki izin tulis (*writable*):
- `storage/`
- `bootstrap/cache/`
- `public/lampiran_pengajuan/`

---

## 6. Bersihkan Cache & Jalankan Aplikasi

Sebelum membuka website pertama kali setelah setup:

```bash
php artisan optimize:clear
```

Lalu jalankan web dengan salah satu cara:

### Opsi 1 — Server Bawaan Laravel (Paling Mudah untuk Lokal)
```bash
php artisan serve
```
Buka browser ke alamat `http://127.0.0.1:8000` (sesuaikan `APP_URL` di `.env`).

### Opsi 2 — Laragon / Apache / Nginx
1. Pastikan project berada di `C:\laragon\www\INVENTARIS-DMK`.
2. Klik **Start All** di Laragon.
3. Akses lewat Virtual Host (misalnya `http://inventaris-dmk.test`) dan pastikan `APP_URL` di `.env` disamakan.

---

## Daftar Akses Portal & Endpoint Utama

| URL / Route | Keterangan |
| :--- | :--- |
| `/` atau `/login` | Halaman login web (Master Admin & Admin) |
| `/masteradmin` | Dashboard Master Admin (`role: master_admin`) |
| `/masteradmin/data-aset` | Kelola Data Aset Master Admin |
| `/masteradmin/tracking-pengadaan` | Tracking pengadaan aset berdasarkan segmen tanggal kode aset |
| `/barcode-aset`, `/laporan`, `/notifikasi`, `/kategori-aset`, `/cabang`, `/kelola-akun`, `/roles`, `/riwayat-aktivitas` | Modul-modul portal Master Admin |
| `/admin-dashboard` atau `/admin` | Dashboard Admin Operasional (`role: admin`) |
| `/admin/data-aset`, `/admin/barcode-aset`, `/admin/tracking-pengadaan`, `/admin/laporan`, `/admin/notifikasi`, `/admin/master-kategori-aset`, `/admin/master-cabang`, `/admin/pengajuan`, `/admin/riwayat-aktivitas` | Modul-modul portal Admin |
| `/_live-sync/version` | Endpoint internal sinkronisasi real-time otomatis tanpa refresh |
| `/dev-console/login` | Developer Security Console (`role: developer` + `DEV_ACCESS_KEY`) |
| `/api/login`, `/api/inventory`, `/api/pengajuan` | REST API untuk aplikasi mobile |

---

## Troubleshooting Cepat Setelah Cloning

1. **Error `No application encryption key has been specified`:**
   Jalankan:
   ```bash
   php artisan key:generate
   php artisan config:clear
   ```
2. **Error `SQLSTATE[HY000] [1049] Unknown database` atau `could not find driver`:**
   - Pastikan service MySQL menyala.
   - Pastikan database bernama sama dengan `DB_DATABASE` di `.env` sudah dibuat.
   - Pastikan ekstensi `pdo_mysql` aktif di `php.ini`.
   - Setelah mengubah `.env`, jalankan `php artisan config:clear`.
3. **QR Code / Lampiran Tidak Muncul (404):**
   - Jalankan `php artisan storage:link`. Jika di Windows folder `public/storage` sudah ada tapi rusak (*broken symlink*), hapus shortcut `public/storage` lalu jalankan ulang `php artisan storage:link`.
4. **Terkena Blokir `429 Too Many Requests` atau Lock 3x Salah Password Saat Testing Lokal:**
   - Bersihkan cache rate limiter dengan `php artisan cache:clear`.
   - Reset penguncian login Admin lokal dengan menghapus/mengosongkan entri di `storage/app/admin_login_locks.json` (atau buka kunci lewat Developer Console / Master Admin).
