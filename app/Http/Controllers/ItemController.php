<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item; // Model untuk Master Aset
use App\Models\DetailAset; // Model untuk data spesifikasi (fisik)
use App\Models\Kategori;
use App\Models\StatusAset;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;
use App\Imports\AsetImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Cabang;

class ItemController extends Controller
{
public function index(Request $request)
    {
        $kategoris = Kategori::paginate(50, ['*'], 'kategori_page');
$items = Item::paginate(50, ['*'], 'item_page');
        $statuses = StatusAset::all(); 
        $cabangs = Cabang::all(); 

        // Mulai Query Database
        $query = \App\Models\DetailAset::with(['item', 'kategori', 'cabang'])->latest('aset_id');

        // 1. Logika Pencarian Teks (Search)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_aset', 'like', "%{$search}%")
                  ->orWhere('merk', 'like', "%{$search}%")
                  ->orWhere('nomor_seri', 'like', "%{$search}%")
                  ->orWhere('Ruangan', 'like', "%{$search}%")
                  ->orWhere('lokasi_saat_ini', 'like', "%{$search}%")
                  ->orWhere('penanggung_jawab', 'like', "%{$search}%")
                  ->orWhere('status_aset', 'like', "%{$search}%")
                  ->orWhereHas('item', function($qItem) use ($search) {
                      $qItem->where('nama_aset', 'like', "%{$search}%");
                  })
                  ->orWhereHas('kategori', function($qKat) use ($search) {
                      $qKat->where('nama_kategori', 'like', "%{$search}%");
                  })
                  ->orWhereHas('cabang', function($qCab) use ($search) {
                      $qCab->where('nama_cabang', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Logika Filter Tipe Aset (Status Berjalan)
        if ($request->filled('filter_tipe') && $request->filter_tipe != 'Semua') {
            $query->where('status_berjalan', $request->filter_tipe);
        }

        // 3. Paginasi 100 data, dan pertahankan parameter URL saat pindah halaman
        $detailAsets = $query->paginate(100)->withQueryString();

        return view('masteradmin.data_aset', compact('kategoris', 'items', 'detailAsets', 'statuses', 'cabangs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            // Tambahkan aturan unique untuk memastikan nama tidak kembar
            'nama_aset'   => 'required|string|max:255|unique:items,nama_aset', 
            'awal_serial' => 'nullable|string', 
            'urutan'      => 'required|integer',
            'warna_seri'  => 'nullable|string'
        ], [
            // Pesan error kustom jika nama aset kembar
            'nama_aset.unique' => 'Nama aset ini sudah terdaftar. Silakan gunakan nama lain.'
        ]);

        $awalSerial = $request->awal_serial ? $request->awal_serial : '-';

        Item::create([
            'kategori_id' => $request->kategori_id,
            'nama_aset'   => $request->nama_aset,
            'awal_serial' => $awalSerial,
            'urutan'      => $request->urutan,
            'warna_seri'  => $request->warna_seri,
        ]);

        return response()->json(['success' => true, 'message' => 'Master aset berhasil disimpan.']);
    }

    public function storeDetail(Request $request)
    {
        // 1. Validasi kolom khusus data fisik aset saja yang wajib diisi
        $request->validate([
            'kategori_id' => 'required',
            'item_id'     => 'required',
            'Ruangan'     => 'required',
            'status_aset' => 'required',
            
        ]);

        // Ambil nama kategori berdasarkan kategori_id yang dikirim dari form
        $kategoriMaster = \App\Models\Kategori::find($request->kategori_id);
        $namaKategori = $kategoriMaster ? $kategoriMaster->nama_kategori : '-';

        // 2. Simpan dengan mencakup seluruh kolom
        $detail = \App\Models\DetailAset::create([
            'item_id' => $request->item_id,
            'kategori_id' => $request->kategori_id,
            'kategori' => $namaKategori,
            'kode_aset' => $request->kode_aset,
            'nomor_seri' => $request->nomor_seri,
            'merk' => $request->merk ?? '-',
            'unit' => $request->unit ?? 1,
            'tanggal_pengadaan' => $request->tanggal_pengadaan ?? date('Y-m-d'),
            'harga_beli' => $request->harga_beli ?? 0,
            'vendor_pengadaan' => $request->vendor_pengadaan ?? '-',
            'no_berita_acara' => $request->no_berita_acara ?? '-',
            'Ruangan' => $request->Ruangan,
            'lokasi_saat_ini' => $request->lokasi_saat_ini ?? '-',
            'penanggung_jawab' => $request->penanggung_jawab ?? '-',
            'status_aset' => $request->status_aset ?? 'Tersedia',
            'status_berjalan' => $request->status_berjalan ?? 'Tidak Ada',
            'status_maintenance' => $request->status_maintenance ?? '-',
            'teknisi' => $request->teknisi ?? '-',
            'biaya_servis' => $request->biaya_servis ?? 0,
            'servis_terakhir' => $request->servis_terakhir ?? date('Y-m-d'),
            'servis_berikutnya' => $request->servis_berikutnya ?? date('Y-m-d'),
            'riwayat_mutasi' => $request->riwayat_mutasi ?? '-',
            'tanggal_mutasi' => $request->tanggal_mutasi ?? date('Y-m-d'),
            'status_peleburan' => $request->status_peleburan ?? '-',
            'tanggal_peleburan' => $request->tanggal_peleburan ?? date('Y-m-d'),
            'metode_peleburan' => $request->metode_peleburan ?? '-',
            'nilai_jual' => $request->nilai_jual ?? 0,
        ]);

        \App\Models\RiwayatAktivitas::create([
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'aktivitas' => 'Menambahkan data aset baru (' . ($request->kode_aset ?? 'Tanpa Kode') . ')',
            'modul' => 'Data Aset',
            'status' => 'Berhasil',
        ]);

        if ($request->hasFile('lampiran')) {
            $paths = [];
            $directory = storage_path('app/public/lampiran_aset');
            if (!\Illuminate\Support\Facades\File::exists($directory)) {
                \Illuminate\Support\Facades\File::makeDirectory($directory, 0755, true);
            }

            foreach ($request->file('lampiran') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '_', $originalName); // Bersihkan nama file dari spasi/simbol
                
                // Jika file berupa gambar, jadikan WEBP
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $filename = time() . '_' . $cleanName . '.webp';
                    $filepath = $directory . '/' . $filename;

                    if ($extension == 'png') {
                        $image = imagecreatefrompng($file->getRealPath());
                        imagepalettetotruecolor($image);
                    } else {
                        $image = imagecreatefromjpeg($file->getRealPath());
                    }

                    // Simpan gambar dalam format WEBP kualitas 80% (sangat ringan)
                    imagewebp($image, $filepath, 80); 
                    imagedestroy($image);

                    $paths[] = 'lampiran_aset/' . $filename;
                } else {
                    // Jika dokumen (PDF, Word, Excel), simpan normal tanpa di-convert
                    $filename = time() . '_' . $cleanName . '.' . $extension;
                    $path = $file->storeAs('lampiran_aset', $filename, 'public');
                    $paths[] = $path;
                }
            }
            // Update data aset dengan deretan nama file
            $detail->update(['lampiran' => json_encode($paths)]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data fisik aset berhasil disimpan!'
            ]);
        }

        return redirect()->back()->with('success', 'Data fisik aset berhasil disimpan!');
    }

    // =========================================================
    // UPDATE MASTER ASET (DARI CHIP EDIT)
    // =========================================================
    public function update(Request $request, $id)
    {
        $request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            // Tambahkan unique dengan pengecualian ID saat ini
            'nama_aset'   => 'required|string|max:255|unique:items,nama_aset,' . $id,
            'awal_serial' => 'nullable|string',
            'urutan'      => 'required|integer',
            'warna_seri'  => 'nullable|string'
        ], [
            'nama_aset.unique' => 'Nama aset ini sudah terdaftar. Silakan gunakan nama lain.'
        ]);

        $item = Item::findOrFail($id);
        
        $awalSerial = $request->awal_serial ? $request->awal_serial : '-';

        $item->update([
            'kategori_id' => $request->kategori_id,
            'nama_aset'   => $request->nama_aset,
            'awal_serial' => $awalSerial,
            'urutan'      => $request->urutan,
            'warna_seri'  => $request->warna_seri,
        ]);

        return response()->json(['success' => true, 'message' => 'Master aset berhasil diupdate.']);
    }

    // =========================================================
    // HAPUS MASTER ASET
    // =========================================================
    public function destroy(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $item->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Master Aset berhasil dihapus!'
            ]);
        }

        return redirect()->back()->with('success', 'Master Aset berhasil dihapus!');
    }

    public function updateDetail(Request $request, $id)
    {
        $detail = \App\Models\DetailAset::findOrFail($id);

        $request->validate([
            'kategori_id' => 'required',
            'Ruangan'     => 'required',
            'status_aset' => 'required',
        ]);

        $kategoriMaster = \App\Models\Kategori::find($request->kategori_id);
        $namaKategori = $kategoriMaster ? $kategoriMaster->nama_kategori : '-';

        // 1. Ambil data lampiran yang lama terlebih dahulu (ubah dari JSON ke Array PHP)
        $existingFiles = json_decode($detail->lampiran, true) ?? [];

        // 2. Jika user mengupload file baru saat edit
        if ($request->hasFile('lampiran')) {
            $directory = storage_path('app/public/lampiran_aset');
            if (!\Illuminate\Support\Facades\File::exists($directory)) {
                \Illuminate\Support\Facades\File::makeDirectory($directory, 0755, true);
            }

            foreach ($request->file('lampiran') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '_', $originalName);
                
                // Konversi gambar ke WEBP
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $filename = time() . '_' . $cleanName . '.webp';
                    $filepath = $directory . '/' . $filename;

                    if ($extension == 'png') {
                        $image = imagecreatefrompng($file->getRealPath());
                        imagepalettetotruecolor($image);
                    } else {
                        $image = imagecreatefromjpeg($file->getRealPath());
                    }

                    imagewebp($image, $filepath, 80); 
                    imagedestroy($image);

                    $existingFiles[] = 'lampiran_aset/' . $filename; // Gabungkan ke array lama
                } else {
                    // Dokumen biasa (PDF, Word, dll)
                    $filename = time() . '_' . $cleanName . '.' . $extension;
                    $path = $file->storeAs('lampiran_aset', $filename, 'public');
                    $existingFiles[] = $path; // Gabungkan ke array lama
                }
            }
        }

        // 3. Update data aset ke database
        $detail->update([
            'item_id'             => $request->item_id ?? $detail->item_id,
            'kategori_id'         => $request->kategori_id,
            'kategori'            => $namaKategori,
            'kode_aset'           => $request->kode_aset ?? $detail->kode_aset,
            'nomor_seri'          => $request->nomor_seri ?? $detail->nomor_seri,
            'merk'                => $request->merk ?? '-',
            'unit'                => $request->unit ?? 1,
            'tanggal_pengadaan'   => $request->tanggal_pengadaan ?? date('Y-m-d'),
            'harga_beli'          => $request->harga_beli ?? 0,
            'vendor_pengadaan'    => $request->vendor_pengadaan ?? '-',
            'no_berita_acara'     => $request->no_berita_acara ?? '-',
            'Ruangan'             => $request->Ruangan,
            'lokasi_saat_ini'     => $request->lokasi_saat_ini ?? '-',
            'penanggung_jawab'    => $request->penanggung_jawab ?? '-',
            'status_aset'         => $request->status_aset ?? 'Tersedia',
            'status_berjalan'     => $request->status_berjalan ?? 'Tidak Ada',
            'status_maintenance'  => $request->status_maintenance ?? '-',
            'teknisi'             => $request->teknisi ?? '-',
            'biaya_servis'        => $request->biaya_servis ?? 0,
            'servis_terakhir'     => $request->filled('servis_terakhir') ? $request->servis_terakhir : null,
            'servis_berikutnya'   => $request->filled('servis_berikutnya') ? $request->servis_berikutnya : null,
            'riwayat_mutasi'      => $request->riwayat_mutasi ?? '-',
            'tanggal_mutasi'      => $request->filled('tanggal_mutasi') ? $request->tanggal_mutasi : null,
            'status_peleburan'    => $request->status_peleburan ?? '-',
            'tanggal_peleburan'   => $request->has('tanggal_peleburan')
                            ? ($request->filled('tanggal_peleburan') ? $request->tanggal_peleburan : null)
                            : $detail->tanggal_peleburan,
            'metode_peleburan'    => $request->metode_peleburan ?? '-',
            'nilai_jual'          => $request->nilai_jual ?? 0,
            'lampiran'            => json_encode($existingFiles), // Simpan gabungan file lama & baru
        ]);

        if (\Illuminate\Support\Facades\Auth::check()) {
            \App\Models\RiwayatAktivitas::create([
                'user_id'   => \Illuminate\Support\Facades\Auth::id(),
                'aktivitas' => 'Memperbarui data fisik aset: ' . $detail->kode_aset,
                'modul'     => 'Data Aset',
                'status'    => 'Berhasil',
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data fisik aset berhasil diperbarui!'
            ]);
        }

        return redirect()->back()->with('success', 'Data fisik aset berhasil diperbarui!');
    }

    // =========================================================
    // HAPUS DATA FISIK ASET
    // =========================================================
    public function destroyDetail(Request $request, $id)
    {
        $detail = \App\Models\DetailAset::findOrFail($id);
        $detail->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data fisik aset berhasil dihapus!'
            ]);
        }

        return redirect()->back()
        ->with('success', 'Data fisik aset berhasil dihapus!');
    }

    public function barcodeIndex(Request $request)
    {
        $query = \App\Models\DetailAset::with(['item', 'kategori', 'cabang'])->latest('aset_id');

        // 1. Logika Pencarian Teks
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_aset', 'like', "%{$search}%")
                  ->orWhere('nomor_seri', 'like', "%{$search}%")
                  ->orWhere('merk', 'like', "%{$search}%")
                  ->orWhere('status_aset', 'like', "%{$search}%")
                  ->orWhereHas('item', function($qItem) use ($search) {
                      $qItem->where('nama_aset', 'like', "%{$search}%");
                  })
                  ->orWhereHas('kategori', function($qKat) use ($search) {
                      $qKat->where('nama_kategori', 'like', "%{$search}%");
                  })
                  // Tepat di bawah sini, tambahkan logika pencarian Cabang:
                  ->orWhereHas('cabang', function($qCabang) use ($search) {
                      $qCabang->where('nama_cabang', 'like', "%{$search}%");
                  });
            });
        }
        
        // 2. Logika Filter Dropdown Tipe Aset
        if ($request->filled('filter_tipe') && $request->filter_tipe !== 'Semua') {
            $query->where('status_berjalan', $request->filter_tipe);
        }

        $detailAsets = $query->paginate(100)->withQueryString();
        $statuses = \App\Models\StatusAset::all(); 
        
        return view('masteradmin.barcode', compact('detailAsets', 'statuses'));
    }

    public function generateAndSaveQr(Request $request, $id)
    {
        try {
            $detail = \App\Models\DetailAset::findOrFail($id);
            $qrDataCombine = $detail->kode_aset;
            
            // Buat kode unik (hash) dari kombinasi kode dan seri aset
            $hash = md5($qrDataCombine);
            
            $directory = storage_path('app/public/qrcodes');
            if (!\Illuminate\Support\Facades\File::exists($directory)) {
                \Illuminate\Support\Facades\File::makeDirectory($directory, 0755, true);
            }

            // Hapus file QR lama milik aset ini (jika ada) agar memori tidak penuh
            $oldFiles = glob($directory . '/qr_aset_' . $detail->aset_id . '_*.svg');
            foreach($oldFiles as $oldFile) {
                \Illuminate\Support\Facades\File::delete($oldFile);
            }

            // Generate format SVG
            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->margin(1)->size(300)->generate($qrDataCombine);

            // Simpan file dengan nama baru yang mengandung HASH
            $filename = 'qr_aset_' . $detail->aset_id . '_' . $hash . '.svg'; 
            $filepath = $directory . '/' . $filename;
            \Illuminate\Support\Facades\File::put($filepath, $qrSvg);

            return response()->json([
                'success' => true,
                'message' => 'QR Code berhasil dibuat dan mutakhir!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat QR! Penyebab: ' . $e->getMessage()
            ]); 
        }
    }

    public function downloadLampiran($filename)
    {
        $path = storage_path('app/public/lampiran_aset/' . $filename);
        
        if (!file_exists($path)) {
            abort(404, 'File lampiran tidak ditemukan di server.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Jika filenya WebP, kita 'sulap' menjadi JPG saat didownload
        if ($extension === 'webp') {
            $image = imagecreatefromwebp($path);
            $newFilename = str_replace('.webp', '.jpg', $filename); // Ubah nama file jadinya .jpg
            
            // Return stream download (Server mengirim data JPG tanpa harus nge-save file JPG-nya)
            return response()->streamDownload(function () use ($image) {
                imagejpeg($image, null, 95); // Kualitas JPG 95%
                imagedestroy($image);
            }, $newFilename, [
                'Content-Type' => 'image/jpeg',
            ]);
        }

        // Jika bukan WebP (seperti PDF atau Excel), langsung download normal
        return response()->download($path);
    }

    public function viewLampiran($filename)
    {
        $path = storage_path('app/public/lampiran_aset/' . $filename);
        
        if (!file_exists($path)) {
            abort(404, 'File lampiran tidak ditemukan di server.');
        }

        // response()->file() akan memaksa browser untuk MENAMPILKAN file (preview), bukan mendownloadnya
        return response()->file($path);
    }

    public function importExcel(Request $request)
{
    // Import multi-sheet: hanya .xlsx / .xls (CSV tidak punya banyak sheet)
    $request->validate([
        'file_excel' => 'required|mimes:xlsx,xls|max:10240', // Maks 10MB
    ]);

    try {
        $file  = $request->file('file_excel');
        $hasil = (new AsetImport)->import($file->getRealPath(), $file->getClientOriginalExtension());

        if (empty($hasil)) {
            return back()->withErrors(['Tidak ada sheet inventaris yang dikenali. Judul sheet harus diawali "Inventaris", mis. "Inventaris DMK (Juanda)".']);
        }

        $total   = array_sum($hasil);
        $rincian = collect($hasil)->map(fn ($jumlah, $cabang) => "$cabang ($jumlah)")->implode(', ');

        return back()->with('success', "Berhasil mengimport $total aset dari " . count($hasil) . " cabang: $rincian");
    } catch (\Exception $e) {
        return back()->withErrors(['Gagal mengimport data: ' . $e->getMessage()]);
    }
}

public function generateSemuaQr()
    {
        // Ambil semua aset yang ada
        $semuaAset = \App\Models\DetailAset::all();
        $berhasil = 0;

        foreach ($semuaAset as $aset) {
            $isiQr = $aset->kode_aset;
            $hash = md5($isiQr);
            $namaFile = 'qr_aset_' . $aset->aset_id . '_' . $hash . '.svg';
            $pathFile = storage_path('app/public/qrcodes/' . $namaFile);

            // Jika QR versi terbaru belum ada, buat baru
            if (!file_exists($pathFile)) {
                // Hapus QR lama milik aset ini jika ada
                $oldFiles = glob(storage_path('app/public/qrcodes/qr_aset_' . $aset->aset_id . '_*.svg'));
                foreach ($oldFiles as $old) { @unlink($old); }

                // Generate QR SVG Baru
                $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->generate($isiQr);
                
                if (!file_exists(storage_path('app/public/qrcodes'))) {
                    mkdir(storage_path('app/public/qrcodes'), 0755, true);
                }
                
                file_put_contents($pathFile, $qrSvg);
                $berhasil++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil memproses dan membuat {$berhasil} label QR baru!"
        ]);
    }
}
