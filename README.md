# Sistem Informasi Inventaris PT DMK (`inventaris-dmk`)

Aplikasi manajemen inventaris aset PT DMK berbasis **Laravel 13** (PHP 8.3+) yang mencakup portal **Master Admin**, portal **Admin Operasional**, **Developer Console**, serta **REST API Mobile** (pengajuan peminjaman, permintaan, peleburan, perpindahan, dan servis aset) dengan proteksi keamanan berlapis.

---

## Prasyarat Sistem

Pastikan perangkat Anda sudah terpasang komponen berikut sebelum mengambil project:

- **Git**
- **PHP `^8.3`** dengan ekstensi aktif:
  - `pdo_mysql` (dan `pdo_sqlite` jika memakai SQLite)
  - `mbstring`
  - `openssl`
  - `fileinfo` (validasi MIME file upload)
  - `gd` (pengolahan gambar/WebP dan QR Code)
  - `zip` (import/export Excel via `maatwebsite/excel`)
- **Composer** (disarankan versi 2.x)
- **Database MySQL / MariaDB** (bisa menggunakan **Laragon** atau **XAMPP**)
- *(Opsional)* **Node.js & npm** (hanya diperlukan jika Anda ingin memodifikasi asset Vite/Tailwind di `resources/css` atau `resources/js`)

Cek versi PHP dan Composer terlebih dahulu di terminal/CMD:

```bash
php -v
composer -V
```

---

## 1. Cara Mengambil Project dari GitHub

Repository ini menggunakan beberapa branch sesuai modul/tim:

- `Satria-(masteradmin)` — Portal Master Admin, Admin, Developer Console, API Mobile, dan proteksi keamanan penuh.
- `Naila-(Admin)` — Sinkron dengan pembaruan terbaru untuk pengembangan dan operasional Admin.
- `Dewi-(user-mobile)` — Branch pengembangan aplikasi user/mobile.

> **Penting:** Gunakan tanda kutip `"..."` pada nama branch karena mengandung tanda kurung `()` agar tidak error di terminal (CMD/PowerShell/Bash).

### Opsi A — Clone Langsung ke Branch Tertentu (Disarankan)

1. Buka folder tujuan di komputer Anda (misal `C:\laragon\www` jika memakai Laragon):
   ```bash
   cd C:\laragon\www
   ```
2. Clone sesuai branch yang ingin digunakan:

   - **Branch `Satria-(masteradmin)`:**
     ```bash
     git clone -b "Satria-(masteradmin)" https://github.com/satriamikaanjay/inventaris-dmk.git
     ```
   - **Branch `Naila-(Admin)`:**
     ```bash
     git clone -b "Naila-(Admin)" https://github.com/satriamikaanjay/inventaris-dmk.git
     ```

3. Masuk ke folder project:
   ```bash
   cd inventaris-dmk
   ```

### Opsi B — Jika Sudah Pernah Clone Sebelumnya (Update / Pindah Branch)

```bash
cd inventaris-dmk
git fetch origin
git checkout "Satria-(masteradmin)"
git pull origin "Satria-(masteradmin)"
```

*(Ganti `"Satria-(masteradmin)"` dengan `"Naila-(Admin)"` apabila ingin berpindah/mengambil update dari branch Naila.)*

---

## 2. Install Dependensi Composer

Folder `vendor/` tidak disertakan di GitHub, sehingga wajib diinstall setelah clone:

```bash
composer install
```

---

## 3. Konfigurasi File `.env` dan Application Key

1. Salin `.env.example` menjadi `.env`:
   - **Windows CMD:**
     ```cmd
     copy .env.example .env
     ```
   - **PowerShell / Git Bash / Linux / macOS:**
     ```bash
     cp .env.example .env
     ```
2. Buat `APP_KEY` baru:
   ```bash
   php artisan key:generate
   ```
3. Buka file `.env` menggunakan text editor, lalu sesuaikan konfigurasinya:

   > **Perhatian:** File `.env.example` secara default berisi `DB_CONNECTION=sqlite`. Jika Anda menggunakan **MySQL (Laragon / XAMPP)**, ubah bagian database di `.env` menjadi seperti berikut:

   ```env
   APP_NAME="Inventaris DMK"
   APP_ENV=local
   APP_DEBUG=false
   APP_URL=http://127.0.0.1:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=inventaris_dmk
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   *(Apabila tetap ingin uji coba cepat memakai SQLite, biarkan `DB_CONNECTION=sqlite` dan pastikan file `database/database.sqlite` tersedia.)*

4. **(Opsional) Konfigurasi Keamanan Tambahan di `.env`:**
   ```env
   # Cloudflare Turnstile untuk captcha form login (kosongkan di lokal jika tidak dipakai)
   TURNSTILE_SITE_KEY=
   TURNSTILE_SECRET_KEY=

   # Kunci akses Developer Console (/dev-console/login) - wajib diganti saat deploy produksi
   DEV_ACCESS_KEY=ganti-dengan-access-key-rahasia
   ```
   > Biarkan `APP_DEBUG=false` apabila website diakses publik atau ditunneling lewat ngrok agar pesan error/stack trace tidak bocor.

---

## 4. Setup Database (Pilih Salah Satu Cara)

Pastikan service **MySQL / MariaDB** di Laragon atau XAMPP sudah berjalan.

### Cara A — Menggunakan File Dump `.sql` (Jika Diberikan File SQL)

1. Buka phpMyAdmin / HeidiSQL / terminal MySQL, lalu buat database kosong bernama `inventaris_dmk`.
2. Import file `.sql` yang diberikan ke dalam database `inventaris_dmk`.
3. Jika ada migrasi tambahan yang belum masuk ke file `.sql`, jalankan:
   ```bash
   php artisan migrate
   ```

### Cara B — Migrasi & Seeder Otomatis dari Laravel (Database Baru / Kosong)

1. Buat database kosong bernama `inventaris_dmk` di MySQL (jika memakai `mysql`).
2. Jalankan migrasi tabel beserta data awal (*seeder*):
   ```bash
   php artisan migrate --seed
   ```
   *(Atau jalankan satu per satu: `php artisan migrate` lalu `php artisan db:seed`.)*

Perintah `php artisan db:seed` menjalankan `RoleSeeder`, `MasterAdminSeeder`, dan `MasterDataSeeder` yang otomatis membuat:
- Data role awal (`Master Admin`, `Admin`, `Cabang`, `Divisi`)
- Sampel kategori, lokasi, status aset, master item, dan detail aset
- **Akun Master Admin awal** (dari `MasterAdminSeeder`):
  - Email: `masteradmin@gmail.com`
  - Password: `password123`
  - *(Jika menjalankan `php artisan db:seed --class=UserSeeder` secara terpisah, juga tersedia akun `master@admin.com` / `password123`. Segera ganti password bawaan setelah login pertama.)*

---

## 5. Setup Storage Link & Izin Direktori

Hubungkan folder `public/storage` ke `storage/app/public` agar lampiran aset dan file QR Code dapat tampil dengan benar:

```bash
php artisan storage:link
```

Pastikan folder berikut dapat ditulisi (*writable*) oleh web server/PHP:
- `storage/`
- `bootstrap/cache/`
- `public/lampiran_pengajuan/`

---

## 6. Menjalankan Aplikasi

### Opsi 1 — Menggunakan Server Bawaan Laravel (Direkomendasikan untuk Development)

```bash
php artisan serve
```

Lalu buka browser dan akses:
- `http://127.0.0.1:8000`

### Opsi 2 — Menggunakan Laragon Virtual Host

1. Pastikan folder project diletakkan di `C:\laragon\www\inventaris-dmk` (atau sesuai nama folder clone Anda).
2. Klik **Start All** di Laragon untuk menyalakan Apache/Nginx & MySQL.
3. Akses melalui alamat virtual host Laragon (misalnya `http://inventaris-dmk.test`) atau tetap jalankan `php artisan serve`.

### *(Opsional)* Build Frontend Assets (Vite)

Jika Anda memodifikasi file di `resources/css/` atau `resources/js/`:

```bash
npm install
npm run build
```

---

## Daftar Akses Halaman & API Utama

| Modul / Endpoint | Keterangan Akses |
| :--- | :--- |
| `/` atau `/login` | Halaman login web (Admin & Master Admin) |
| `/masteradmin` | Dashboard Master Admin *(khusus role `master_admin`)* |
| `/admin-dashboard` | Dashboard Admin Operasional *(khusus role `admin`)* |
| `/pengajuan` / `/admin/pengajuan` | Kelola pengajuan & lampiran *(role `admin` / `master_admin`)* |
| `/dev-console/login` | Developer Console *(role `developer` + `DEV_ACCESS_KEY`)* |
| `/api/login`, `/api/inventory`, `/api/pengajuan` | Endpoint REST API untuk aplikasi mobile |

---

## Catatan Keamanan & Troubleshooting

- **Error `No application encryption key has been specified`:**
  Jalankan `php artisan key:generate` lalu `php artisan config:clear`.
- **Error `SQLSTATE[HY000] [1049] Unknown database` atau `could not find driver`:**
  Pastikan database `inventaris_dmk` sudah dibuat di MySQL, service MySQL aktif, serta `DB_CONNECTION=mysql` di `.env` sudah sesuai (termasuk ekstensi `pdo_mysql` aktif di `php.ini`).
- **Terkena blokir sementara `429 Too Many Requests` atau Lock Login 3x Gagal saat testing:**
  Sistem memiliki proteksi WAF, Anti-DoS burst limiter, dan penguncian 3x gagal login (`storage/app/admin_login_locks.json` untuk Admin serta rate-limit cache untuk Master Admin). Saat pengujian lokal, Anda bisa membersihkan cache rate-limit melalui:
  ```bash
  php artisan cache:clear
  ```
  Jika akun Admin terkunci permanen di lingkungan lokal akibat uji coba salah password 3x, hapus/reset file `storage/app/admin_login_locks.json` atau buka kunci dari panel admin/master admin yang berwenang.
