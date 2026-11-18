# Aplikasi Pengajuan DMK (User Mobile)

Aplikasi Android berbasis Kotlin untuk pengguna cabang pada sistem **Inventaris DMK**. Aplikasi ini terhubung langsung ke REST API backend web Laravel (`INVENTARIS-DMK`) dan mendukung koneksi via **Wi-Fi (jaringan lokal yang sama)** maupun **kabel USB (`adb reverse`)**.

---

## Fitur Utama

- **Auto-Discovery Wi-Fi & Status Server Real-Time:** Saat berpindah ke Wi-Fi mana pun (Wi-Fi kampus, rumah, atau hotspot HP), aplikasi otomatis memindai subnet Wi-Fi lokal untuk menemukan server web laptop (`Apache` port `80` maupun `artisan serve` port `8000`) tanpa harus edit kode. Tersedia juga tombol **Atur IP** dan **Cari Server Otomatis di Wi-Fi** untuk pengaturan manual.
- **Autentikasi & Status Akses Cabang:** Login akun user cabang yang terhubung dengan status akses dari web.
- **Inventaris & Detail Aset:** Melihat daftar aset per kategori, jumlah unit cabang, detail aset, serta tipe status aset (`Aset Berjalan`, `Aset Tidak Berjalan`, `Aset Tidak Dipakai`).
- **Data Stok Barang (Sinkron Web):** Melihat seluruh data stok barang dari tabel web (`/api/data-stok`), dilengkapi ringkasan statistik (total jenis barang, stok tersedia, stok habis), pencarian real-time, filter status ketersediaan, pull-to-refresh, dan pop-up detail barang.
- **Scan QR / Barcode Aset:** Menggunakan CameraX dan Google ML Kit untuk memindai kode aset secara langsung.
- **Pengajuan Kerusakan, Perbaikan & Perpindahan:**
  - Mendukung lampiran foto/dokumen.
  - Pada **Pengajuan Perpindahan**, tersedia pilihan ruangan dari dropdown maupun input manual nama ruangan baru apabila ruangan tujuan belum terdaftar di dropdown.
- **Riwayat & Notifikasi Pengajuan:** Memantau status pengajuan yang masuk dan diproses oleh admin web.

---

## Prasyarat & Software yang Wajib Diinstall

Sebelum melakukan cloning dan menjalankan project ini di laptop/PC baru, siapkan software berikut:

1. **Git**
   - Unduh dan install Git: https://git-scm.com/downloads
2. **Android Studio (Versi Terbaru)**
   - Wajib mendukung **Android Gradle Plugin (AGP) `9.3.3`** dan **Gradle `9.5.0`** (lihat `gradle/libs.versions.toml` dan `gradle/wrapper/gradle-wrapper.properties`).
3. **JDK 25 (untuk Gradle Daemon)**
   - Project ini mengonfigurasi `toolchainVersion=25` di `gradle/gradle-daemon-jvm.properties` (dengan `sourceCompatibility` dan `targetCompatibility` Java 11).
   - Saat Gradle Sync pertama kali dengan koneksi internet aktif, Gradle dapat mengunduh toolchain secara otomatis, atau pastikan **Gradle JDK** di Android Studio mengarah ke JDK yang kompatibel.
4. **Android SDK Components** (install melalui **Android Studio → Tools → SDK Manager**):
   - **SDK Platforms:** Centang **Android API 37** (`compileSdk = 37`, `targetSdk = 37`, `minSdk = 24`).
   - **SDK Tools:** Centang **Android SDK Build-Tools** (versi terbaru untuk API 37) dan **Android SDK Platform-Tools** (berisi perintah `adb`).
5. **Backend Web Laravel (`INVENTARIS-DMK`) + Laragon**
   - Pastikan **Apache** dan **MySQL** berjalan di Laragon, file `.env` web sudah dikonfigurasi, serta database sudah di-migrate & seed.

---

## Cara Cloning Repository

Jalankan perintah berikut di terminal / PowerShell untuk mengambil branch `Dewi-(user-mobile)`:

```powershell
git clone -b "Dewi-(user-mobile)" --single-branch https://github.com/satriamikaanjay/inventaris-dmk.git
cd inventaris-dmk
```

---

## Cara Membuka Project & Gradle Sync

1. Buka **Android Studio**, pilih **Open**, lalu arahkan ke folder `inventaris-dmk` hasil clone.
2. Tunggu proses **Gradle Sync** hingga selesai (pastikan laptop terhubung ke internet pada sync pertama untuk mengunduh Gradle `9.5.0` dan dependensi seperti Retrofit, OkHttp, CameraX, dan ML Kit).
3. File `local.properties` tidak ikut tersimpan di GitHub (diabaikan oleh `.gitignore`) karena berisi path SDK lokal tiap komputer.
   - Biasanya Android Studio membuat file ini secara otomatis saat project dibuka.
   - Jika muncul error *SDK location not found*, buat atau edit file `local.properties` di folder root project dan isi dengan lokasi Android SDK Anda:
     ```properties
     sdk.dir=C\:\\Users\\<NAMA_USER_WINDOWS>\\AppData\\Local\\Android\\Sdk
     ```

---

## Konfigurasi Server Lokal (Wajib Disesuaikan Sebelum Mencoba)

Project ini mendukung **3 cara koneksi** ke backend Laravel di laptop Anda.

### Cara 1: Ubah IP Langsung di Aplikasi HP (Tanpa Edit Kode — Paling Praktis)
1. Cari **IPv4 Address** laptop Anda lewat Command Prompt / PowerShell:
   ```powershell
   ipconfig
   ```
   *(Contoh: `192.168.1.15` atau `172.14.6.31` pada bagian **Wireless LAN adapter Wi-Fi**).*
2. Install & buka aplikasi di HP, lalu tekan tombol **Atur IP** pada kotak status server di halaman Login.
3. Masukkan IPv4 laptop Anda dan pilih mode server yang dijalankan di laptop:
   - **Laragon Apache Wi-Fi (Port 80)** → URL menjadi `http://<IP_LAPTOP>/INVENTARIS-DMK/public/api/`
     *(Cocok jika Laragon berjalan normal dan folder web bernama `C:\laragon\www\INVENTARIS-DMK`).*
   - **Artisan Serve Wi-Fi (Port 8000)** → URL menjadi `http://<IP_LAPTOP>:8000/api/`
     *(Jalankan di folder web Laravel: `php artisan serve --host=0.0.0.0 --port=8000`).*
   - **Kabel USB (`127.0.0.1:8000`)** → URL menjadi `http://127.0.0.1:8000/api/`
     *(Cocok jika HP terhubung kabel USB dan tidak ingin memakai Wi-Fi LAN).*

### Cara 2: Ubah Default Permanen di Kode (Opsional)
Jika ingin alamat default aplikasi langsung mengarah ke IP laptop baru saat pertama kali di-build:
1. Buka file:
   `app/src/main/java/com/example/aplikasi_inventaris_dmk/network/ApiClient.kt`
2. Ubah nilai `DEFAULT_HOST_IP`:
   ```kotlin
   const val DEFAULT_HOST_IP = "192.168.1.15" // Ganti dengan IPv4 laptop Anda
   ```
3. *(Khusus Mode Apache)* Jika nama folder project web di `C:\laragon\www\` **berbeda** dari `INVENTARIS-DMK`, sesuaikan path di `DEFAULT_APACHE_URL` dan helper URL pada `ApiClient.kt` (atau gunakan saja mode `php artisan serve --host=0.0.0.0 --port=8000` agar tidak bergantung pada nama folder).

---

## Persiapan Jaringan & Menjalankan Server Web

### Opsi A — Tanpa Kabel (Menggunakan Wi-Fi yang Sama)
1. Pastikan **HP Android dan Laptop terhubung ke Wi-Fi / hotspot yang sama** (hindari kondisi laptop memakai kabel LAN/Ethernet yang terisolasi dari jaringan Wi-Fi HP).
2. Jalankan backend Laravel dengan salah satu cara:
   - **Menggunakan Apache Laragon:** klik **Start All** di Laragon (port 80).
   - **Menggunakan Artisan Serve:** buka terminal di folder web `INVENTARIS-DMK`, lalu jalankan:
     ```powershell
     php artisan serve --host=0.0.0.0 --port=8000
     ```
3. Pastikan **Windows Defender Firewall** tidak memblokir koneksi masuk pada port `80` (Apache) atau `8000` (`php.exe`). Jika status di HP tetap offline padahal IP sudah benar, buat *Inbound Rule* di Windows Firewall untuk mengizinkan TCP port `80` dan `8000` (atau ubah profil jaringan Wi-Fi Windows menjadi **Private**).

### Opsi B — Menggunakan Kabel USB (`adb reverse`)
Jika Wi-Fi memblokir komunikasi antar-perangkat (AP Isolation):
1. Jalankan Laravel di laptop:
   ```powershell
   php artisan serve --port=8000
   ```
2. Sambungkan HP ke laptop dengan kabel USB (USB Debugging aktif), lalu jalankan forwarding port:
   ```powershell
   adb reverse tcp:8000 tcp:8000
   ```
   *(Jika `adb` belum terdaftar di PATH Windows, jalankan menggunakan path lengkap: `& "$env:LOCALAPPDATA\Android\Sdk\platform-tools\adb.exe" reverse tcp:8000 tcp:8000`).*
3. Pada aplikasi di HP, pilih **Atur IP** → pilih mode **USB (`127.0.0.1:8000`)**.

---

## Cara Build & Run ke HP Fisik

1. Di HP Android, aktifkan **Opsi Pengembang (Developer Options)** dan nyalakan **USB Debugging** (atau **Wireless Debugging** pada Android 11+).
2. Sambungkan HP ke laptop hingga nama perangkat muncul di toolbar Android Studio.
3. Klik **Run ▶** (`app`) untuk menginstall dan menjalankan aplikasi langsung ke HP.
4. Untuk menghasilkan file APK secara terpisah:
   - Klik **Build → Build App Bundle(s) / APK(s) → Build APK(s)**.
   - File APK akan tersimpan di `app/build/outputs/apk/debug/app-debug.apk`.
5. Saat pertama kali dijalankan di HP:
   - Tunggu banner status di halaman Login berubah menjadi hijau **"Terhubung dengan server"**.
   - Berikan izin **Kamera** (untuk Scan QR), **Notifikasi**, dan **Penyimpanan/File** (saat mengunggah lampiran) bila diminta.

---

## Troubleshooting Cepat

| Masalah | Penyebab Umum | Solusi |
| :--- | :--- | :--- |
| Status di aplikasi tetap **Tidak terhubung ke server** | IP salah, beda Wi-Fi/subnet, atau terblokir Firewall | Cek ulang IP dengan `ipconfig`, pastikan satu Wi-Fi, tes buka `http://<IP>/INVENTARIS-DMK/public/api/inventory/categories` dari browser HP. |
| `adb : The term 'adb' is not recognized` | `platform-tools` belum masuk ke Environment Variable `PATH` | Tambahkan `C:\Users\<USER>\AppData\Local\Android\Sdk\platform-tools` ke `PATH` atau panggil langsung `adb.exe` dari folder tersebut. |
| Error `SDK location not found` | File `local.properties` belum ada setelah clone | Isi `sdk.dir` di `local.properties` atau klik *File → Project Structure → SDK Location* di Android Studio. |
| `php artisan serve` tidak bisa diakses dari HP | Dijalankan tanpa `--host=0.0.0.0` | Jalankan ulang dengan `php artisan serve --host=0.0.0.0 --port=8000`. |
