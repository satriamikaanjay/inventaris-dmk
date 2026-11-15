<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\DetailAset;
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

        $query = \App\Models\DetailAset::with(['item', 'kategori', 'cabang'])->orderBy('aset_id', $request->sort === 'terlama' ? 'asc' : 'desc');

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

        if ($request->filled('filter_tipe') && $request->filter_tipe != 'Semua') {
            $query->where('status_berjalan', $request->filter_tipe);
        }

        $detailAsets = $query->paginate(100)->withQueryString();

        return view('masteradmin.data_aset', compact('kategoris', 'items', 'detailAsets', 'statuses', 'cabangs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'nama_aset'   => 'required|string|max:255|unique:items,nama_aset', 
            'awal_serial' => 'nullable|string', 
            'urutan'      => 'required|integer',
            'warna_seri'  => 'nullable|string'
        ], [
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
        $request->validate([
            'kategori_id' => 'required',
            'item_id'     => 'required',
            'Ruangan'     => 'nullable|string|max:255',
            'status_aset' => 'required',
            'lampiran.*'  => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120',
        ], [
            'lampiran.*.mimes' => 'Format file lampiran tidak sesuai! Hanya diizinkan format JPG, PNG, PDF, DOC, dan XLS.',
            'lampiran.*.max'   => 'Ukuran file lampiran maksimal 5 MB per file.',
        ]);

        if ($request->hasFile('lampiran')) {
            foreach ($request->file('lampiran') as $fileCheck) {
                $pesanErrorFile = $this->validasiKeamananFileUpload($fileCheck);
                if ($pesanErrorFile !== null) {
                    return redirect()->back()->withErrors(['lampiran' => $pesanErrorFile])->withInput();
                }
            }
        }

        $kategoriMaster = \App\Models\Kategori::find($request->kategori_id);
        $namaKategori = $kategoriMaster ? $kategoriMaster->nama_kategori : '-';

        $detail = \App\Models\DetailAset::create([
            'item_id' => $request->item_id,
            'kategori_id' => $request->kategori_id,
            'cabang_id' => $request->cabang_id,
            'kategori' => $namaKategori,
            'kode_aset' => $request->kode_aset,
            'nomor_seri' => $request->nomor_seri ?? '',
            'merk' => $request->merk ?? '-',
            'unit' => $request->unit ?? 1,
            'tanggal_pengadaan' => $request->tanggal_pengadaan ?? date('Y-m-d'),
            'harga_beli' => $request->harga_beli ?? 0,
            'vendor_pengadaan' => $request->vendor_pengadaan ?? '-',
            'no_berita_acara' => $request->no_berita_acara ?? '-',
            'Ruangan' => \App\Models\DetailAset::normalisasiRuangan($request->Ruangan),
            'lokasi_saat_ini' => $request->lokasi_saat_ini ?? '-',
            'penanggung_jawab' => $request->penanggung_jawab ?? '-',
            'status_aset' => $request->status_aset ?? 'Tersedia',
            'status_berjalan' => $request->status_berjalan ?? 'Tidak Ada',
            'status_maintenance' => $request->status_maintenance ?? '-',
            'teknisi' => $request->teknisi ?? '-',
            'biaya_servis' => $request->biaya_servis ?? 0,
            'servis_terakhir' => $request->filled('servis_terakhir') ? $request->servis_terakhir : null,
            'servis_berikutnya' => $request->filled('servis_berikutnya') ? $request->servis_berikutnya : null,
            'riwayat_mutasi' => $request->riwayat_mutasi ?? '-',
            'tanggal_mutasi' => $request->filled('tanggal_mutasi') ? $request->tanggal_mutasi : null,
            'status_peleburan' => $request->status_peleburan ?? '-',
            'tanggal_peleburan' => $request->filled('tanggal_peleburan') ? $request->tanggal_peleburan : null,
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
                $cleanName = substr(preg_replace('/[^A-Za-z0-9\-]/', '_', $originalName), 0, 50);
                $randToken = bin2hex(random_bytes(4));
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $filename = time() . '_' . $randToken . '_' . $cleanName . '.webp';
                    $filepath = $directory . '/' . $filename;

                    if ($extension == 'png') {
                        $image = @imagecreatefrompng($file->getRealPath());
                        if ($image !== false) {
                            imagepalettetotruecolor($image);
                        }
                    } else {
                        $image = @imagecreatefromjpeg($file->getRealPath());
                    }

                    if ($image !== false) {
                        imagewebp($image, $filepath, 80);
                        imagedestroy($image);
                        $paths[] = 'lampiran_aset/' . $filename;
                    }
                } else {
                    $filename = time() . '_' . $randToken . '_' . $cleanName . '.' . $extension;
                    $path = $file->storeAs('lampiran_aset', $filename, 'public');
                    $paths[] = $path;
                }
            }
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
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
            'Ruangan'     => 'nullable|string|max:255',
            'status_aset' => 'required',
            'lampiran.*'  => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120',
        ], [
            'lampiran.*.mimes' => 'Format file lampiran tidak sesuai! Hanya diizinkan format JPG, PNG, PDF, DOC, dan XLS.',
            'lampiran.*.max'   => 'Ukuran file lampiran maksimal 5 MB per file.',
        ]);

        if ($request->hasFile('lampiran')) {
            foreach ($request->file('lampiran') as $fileCheck) {
                $pesanErrorFile = $this->validasiKeamananFileUpload($fileCheck);
                if ($pesanErrorFile !== null) {
                    return redirect()->back()->withErrors(['lampiran' => $pesanErrorFile])->withInput();
                }
            }
        }

        $kategoriMaster = \App\Models\Kategori::find($request->kategori_id);
        $namaKategori = $kategoriMaster ? $kategoriMaster->nama_kategori : '-';

        $existingFiles = json_decode($detail->lampiran, true) ?? [];

        if ($request->hasFile('lampiran')) {
            $directory = storage_path('app/public/lampiran_aset');
            if (!\Illuminate\Support\Facades\File::exists($directory)) {
                \Illuminate\Support\Facades\File::makeDirectory($directory, 0755, true);
            }

            foreach ($request->file('lampiran') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $cleanName = substr(preg_replace('/[^A-Za-z0-9\-]/', '_', $originalName), 0, 50);
                $randToken = bin2hex(random_bytes(4));
                
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $filename = time() . '_' . $randToken . '_' . $cleanName . '.webp';
                    $filepath = $directory . '/' . $filename;

                    if ($extension == 'png') {
                        $image = @imagecreatefrompng($file->getRealPath());
                        if ($image !== false) {
                            imagepalettetotruecolor($image);
                        }
                    } else {
                        $image = @imagecreatefromjpeg($file->getRealPath());
                    }

                    if ($image !== false) {
                        imagewebp($image, $filepath, 80); 
                        imagedestroy($image);
                        $existingFiles[] = 'lampiran_aset/' . $filename;
                    }
                } else {
                    $filename = time() . '_' . $randToken . '_' . $cleanName . '.' . $extension;
                    $path = $file->storeAs('lampiran_aset', $filename, 'public');
                    $existingFiles[] = $path;
                }
            }
        }

        $detail->update([
            'item_id'             => $request->item_id ?? $detail->item_id,
            'kategori_id'         => $request->kategori_id,
            'cabang_id'           => $request->cabang_id ?? $detail->cabang_id,
            'kategori'            => $namaKategori,
            'kode_aset'           => $request->kode_aset ?? $detail->kode_aset,
            'nomor_seri'          => $request->nomor_seri ?? $detail->nomor_seri,
            'merk'                => $request->merk ?? '-',
            'unit'                => $request->unit ?? 1,
            'tanggal_pengadaan'   => $request->tanggal_pengadaan ?? date('Y-m-d'),
            'harga_beli'          => $request->harga_beli ?? 0,
            'vendor_pengadaan'    => $request->vendor_pengadaan ?? '-',
            'no_berita_acara'     => $request->no_berita_acara ?? '-',
            'Ruangan'             => \App\Models\DetailAset::normalisasiRuangan($request->Ruangan),
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
            'lampiran'            => json_encode($existingFiles),
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

        if ($request->filled('search')) {
            $rawSearch = trim((string) $request->search);
            $words = preg_split('/\s+/', $rawSearch, -1, PREG_SPLIT_NO_EMPTY) ?: [$rawSearch];

            foreach ($words as $word) {
                $query->where(function ($q) use ($word) {
                    $q->where('kode_aset', 'like', "%{$word}%")
                      ->orWhere('nomor_seri', 'like', "%{$word}%")
                      ->orWhere('merk', 'like', "%{$word}%")
                      ->orWhere('Ruangan', 'like', "%{$word}%")
                      ->orWhere('lokasi_saat_ini', 'like', "%{$word}%")
                      ->orWhere('penanggung_jawab', 'like', "%{$word}%")
                      ->orWhere('status_aset', 'like', "%{$word}%")
                      ->orWhere('status_berjalan', 'like', "%{$word}%")
                      ->orWhere('status_maintenance', 'like', "%{$word}%")
                      ->orWhere('status_peleburan', 'like', "%{$word}%")
                      ->orWhere('metode_peleburan', 'like', "%{$word}%")
                      ->orWhere('vendor_pengadaan', 'like', "%{$word}%")
                      ->orWhere('teknisi', 'like', "%{$word}%")
                      ->orWhere('no_berita_acara', 'like', "%{$word}%")
                      ->orWhere('riwayat_mutasi', 'like', "%{$word}%")
                      ->orWhere('tanggal_pengadaan', 'like', "%{$word}%")
                      ->orWhere('unit', 'like', "%{$word}%")
                      ->orWhere('harga_beli', 'like', "%{$word}%")
                      ->orWhereHas('item', function ($qItem) use ($word) {
                          $qItem->where('nama_aset', 'like', "%{$word}%")
                                ->orWhere('awal_serial', 'like', "%{$word}%")
                                ->orWhere('warna_seri', 'like', "%{$word}%");
                      })
                      ->orWhereHas('kategori', function ($qKat) use ($word) {
                          $qKat->where('nama_kategori', 'like', "%{$word}%")
                               ->orWhere('kode_kategori', 'like', "%{$word}%");
                      })
                      ->orWhereHas('cabang', function ($qCabang) use ($word) {
                          $qCabang->where('nama_cabang', 'like', "%{$word}%");
                      });
                });
            }
        }
        
        if ($request->filled('filter_tipe') && $request->filter_tipe !== 'Semua') {
            if ($request->filter_tipe === 'Tidak Ada') {
                $query->where(function ($q) {
                    $q->whereNull('status_berjalan')
                      ->orWhere('status_berjalan', '')
                      ->orWhere('status_berjalan', 'Tidak Ada');
                });
            } else {
                $query->where('status_berjalan', $request->filter_tipe);
            }
        }

        $detailAsets = $query->paginate(100)->withQueryString();
        $statuses = \App\Models\StatusAset::all(); 

        $qrDir = storage_path('app/public/qrcodes');
        $setFileQrExact = [];
        $mapFileQrByAsetId = [];
        if (is_dir($qrDir)) {
            foreach (@scandir($qrDir) ?: [] as $fn) {
                if (substr($fn, -4) === '.svg' && strpos($fn, 'qr_aset_') === 0) {
                    $setFileQrExact[$fn] = true;
                    if (preg_match('/^qr_aset_(\d+)_/', $fn, $mQr)) {
                        $idAsetMap = (int) $mQr[1];
                        if (!isset($mapFileQrByAsetId[$idAsetMap])) {
                            $mapFileQrByAsetId[$idAsetMap] = $fn;
                        }
                    }
                }
            }
        }

        $petaWarnaStatus = [];
        foreach ($statuses as $stItem) {
            if (!empty($stItem->nama_status) && !empty($stItem->warna_status)) {
                $petaWarnaStatus[$stItem->nama_status] = $stItem->warna_status;
            }
        }

        $baseQrStorageUrl = asset('storage/qrcodes');
        $siapkanAtributQr = function ($rowQr) use ($petaWarnaStatus, $setFileQrExact, $mapFileQrByAsetId, $baseQrStorageUrl) {
            $wBg = $petaWarnaStatus[$rowQr->status_aset] ?? '#94a3b8';
            $hx = ltrim($wBg, '#');
            $cBg = 'rgba(148, 163, 184, 0.08)';
            if (strlen($hx) === 6) {
                $cBg = 'rgba(' . hexdec(substr($hx, 0, 2)) . ', ' . hexdec(substr($hx, 2, 2)) . ', ' . hexdec(substr($hx, 4, 2)) . ', 0.08)';
            } elseif (strlen($hx) === 3) {
                $cBg = 'rgba(' . hexdec(str_repeat(substr($hx, 0, 1), 2)) . ', ' . hexdec(str_repeat(substr($hx, 1, 1), 2)) . ', ' . hexdec(str_repeat(substr($hx, 2, 1), 2)) . ', 0.08)';
            }
            $expFile = 'qr_aset_' . $rowQr->aset_id . '_' . md5((string) $rowQr->kode_aset) . '.svg';
            $upToDate = isset($setFileQrExact[$expFile]);
            $anyQr = $upToDate || isset($mapFileQrByAsetId[(int) $rowQr->aset_id]);
            $showFile = $upToDate ? $expFile : ($mapFileQrByAsetId[(int) $rowQr->aset_id] ?? null);
            $namaItem = $rowQr->item->nama_aset ?? 'Tanpa Nama';
            $namaKat = $rowQr->kategori->nama_kategori ?? (is_string($rowQr->kategori) ? $rowQr->kategori : '-');
            $namaCab = $rowQr->cabang->nama_cabang ?? '-';
            $ruanganVal = ($rowQr->Ruangan && $rowQr->Ruangan !== '-') ? $rowQr->Ruangan : '-';
            $tipeBerjalanVal = $rowQr->status_berjalan ?? 'Tidak Ada';
            $blobText = strtolower(implode(' ', array_filter([
                $rowQr->kode_aset ?? '',
                $namaItem,
                $rowQr->item->awal_serial ?? '',
                $namaKat,
                $rowQr->kategori->kode_kategori ?? '',
                $namaCab,
                $rowQr->Ruangan ?? '',
                $rowQr->lokasi_saat_ini ?? '',
                $rowQr->merk ?? '',
                $rowQr->nomor_seri ?? '',
                $rowQr->penanggung_jawab ?? '',
                $rowQr->status_aset ?? '',
                $tipeBerjalanVal,
                $rowQr->status_maintenance ?? '',
                $rowQr->status_peleburan ?? '',
                $rowQr->vendor_pengadaan ?? '',
                $rowQr->teknisi ?? '',
                $rowQr->no_berita_acara ?? '',
                $rowQr->riwayat_mutasi ?? '',
                $rowQr->tanggal_pengadaan ?? '',
                $rowQr->unit ?? '',
            ])));
            return [
                'id' => $rowQr->aset_id,
                'nama' => $namaItem,
                'kategori' => $namaKat,
                'status_aset' => $rowQr->status_aset ?? 'N/A',
                'kode_aset' => $rowQr->kode_aset ?? '-',
                'nomor_seri' => $rowQr->nomor_seri ?? '-',
                'merk' => $rowQr->merk ?? '-',
                'cabang' => $namaCab,
                'ruangan' => $ruanganVal,
                'status_berjalan' => $tipeBerjalanVal,
                'warna_bg' => $wBg,
                'card_bg' => $cBg,
                'has_qr' => $anyQr,
                'up_to_date' => $upToDate,
                'file_qr' => $showFile,
                'url_qr' => $showFile ? ($baseQrStorageUrl . '/' . $showFile) : '',
                'search' => $blobText,
            ];
        };

        foreach ($detailAsets as $dtItem) {
            $attr = $siapkanAtributQr($dtItem);
            $dtItem->qr_attr = $attr;
        }

        $semuaAsetGlobal = \App\Models\DetailAset::with(['item', 'kategori', 'cabang'])->orderBy('aset_id', 'desc')->get();
        $katalogSemuaQr = [];
        foreach ($semuaAsetGlobal as $rowGlobal) {
            $katalogSemuaQr[] = $siapkanAtributQr($rowGlobal);
        }
        
        return view('masteradmin.barcode', compact('detailAsets', 'statuses', 'katalogSemuaQr'));
    }

    public function generateAndSaveQr(Request $request, $id)
    {
        try {
            $detail = \App\Models\DetailAset::findOrFail($id);
            $qrDataCombine = $detail->kode_aset;
            
            $hash = md5($qrDataCombine);
            
            $directory = storage_path('app/public/qrcodes');
            if (!\Illuminate\Support\Facades\File::exists($directory)) {
                \Illuminate\Support\Facades\File::makeDirectory($directory, 0755, true);
            }

            $oldFiles = glob($directory . '/qr_aset_' . $detail->aset_id . '_*.svg');
            foreach($oldFiles as $oldFile) {
                \Illuminate\Support\Facades\File::delete($oldFile);
            }

            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->margin(1)->size(300)->generate($qrDataCombine);

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
        $path = $this->ambilPathLampiranAman((string) $filename);
        $safeFilename = basename($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'webp') {
            $image = @imagecreatefromwebp($path);
            if ($image !== false) {
                $newFilename = preg_replace('/\.webp$/i', '.jpg', $safeFilename);
                return response()->streamDownload(function () use ($image) {
                    imagejpeg($image, null, 95);
                    imagedestroy($image);
                }, $newFilename, [
                    'Content-Type'           => 'image/jpeg',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        return response()->download($path, $safeFilename, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function viewLampiran($filename)
    {
        $path = $this->ambilPathLampiranAman((string) $filename);

        return response()->file($path, [
            'X-Content-Type-Options'  => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox;",
        ]);
    }

    private function ambilPathLampiranAman(string $filename): string
    {
        $cleanFilename = basename(trim($filename));
        if ($cleanFilename === '' || str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            abort(403, 'Akses ditolak: Nama file tidak valid.');
        }

        $allowedReadExt = ['webp', 'jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $ext = strtolower(pathinfo($cleanFilename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedReadExt, true)) {
            abort(403, 'Akses ditolak: Format file tidak diizinkan.');
        }

        $baseDir = realpath(storage_path('app/public/lampiran_aset'));
        $fullPath = storage_path('app/public/lampiran_aset/' . $cleanFilename);
        $realFullPath = realpath($fullPath);

        if ($baseDir === false || $realFullPath === false || !str_starts_with($realFullPath, $baseDir) || !is_file($realFullPath)) {
            abort(404, 'File lampiran tidak ditemukan di server.');
        }

        return $realFullPath;
    }

    private function validasiKeamananFileUpload($file): ?string
    {
        return \App\Support\FileUploadSecurity::validateUploadedFile($file);
    }

    public function importExcel(Request $request)
{
    $request->validate([
        'file_excel' => 'required|file|mimes:xlsx,xls|max:10240',
    ]);

    try {
        $file  = $request->file('file_excel');
        $pesanErrorExcel = $this->validasiKeamananFileUpload($file);
        if ($pesanErrorExcel !== null) {
            return back()->withErrors([$pesanErrorExcel]);
        }
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
        $semuaAset = \App\Models\DetailAset::all();
        $berhasil = 0;

        foreach ($semuaAset as $aset) {
            $isiQr = $aset->kode_aset;
            $hash = md5($isiQr);
            $namaFile = 'qr_aset_' . $aset->aset_id . '_' . $hash . '.svg';
            $pathFile = storage_path('app/public/qrcodes/' . $namaFile);

            if (!file_exists($pathFile)) {
                $oldFiles = glob(storage_path('app/public/qrcodes/qr_aset_' . $aset->aset_id . '_*.svg'));
                foreach ($oldFiles as $old) { @unlink($old); }

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

    public function bulkDelete(Request $request)
{
    $ids = $request->input('ids');

    if (!empty($ids)) {
        $terdampak = DetailAset::whereIn('aset_id', $ids)
            ->get(['item_id', 'cabang_id', 'Ruangan']);

        DetailAset::whereIn('aset_id', $ids)->delete();

        $sudahDisinkron = [];
        foreach ($terdampak as $row) {
            $ruangNorm = DetailAset::normalisasiRuangan($row->Ruangan);
            $kunci = $row->item_id . '|' . ($row->cabang_id ?? 'null') . '|' . ($ruangNorm === '-' ? '__NO_ROOM__' : mb_strtolower($ruangNorm));
            if (!isset($sudahDisinkron[$kunci])) {
                $sudahDisinkron[$kunci] = true;
                DetailAset::sinkronkanUnitKelompok($row->item_id, $row->cabang_id, $row->Ruangan);
            }
        }

        return redirect()->back()->with('success', count($ids) . ' data aset berhasil dihapus secara massal.');
    }

    return redirect()->back()->withErrors(['Pilih minimal satu aset untuk dihapus.']);
}
}
