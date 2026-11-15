<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminDetailAset;
use App\Models\AdminStatusAset;
use Illuminate\Support\Facades\File;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminBarcodeController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminDetailAset::with(['item', 'kategori', 'cabang']);
        
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
                      ->orWhereHas('cabang', function ($qCab) use ($word) {
                          $qCab->where('nama_cabang', 'like', "%{$word}%");
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

        $detailAsets = $query->paginate(50)->withQueryString();
        $statuses = AdminStatusAset::all();

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

        $semuaAsetGlobal = AdminDetailAset::with(['item', 'kategori', 'cabang'])->orderBy('aset_id', 'desc')->get();
        $katalogSemuaQr = [];
        foreach ($semuaAsetGlobal as $rowGlobal) {
            $katalogSemuaQr[] = $siapkanAtributQr($rowGlobal);
        }

        return view('admin.barcode', compact('detailAsets', 'statuses', 'katalogSemuaQr'));
    }

    private function simpanQr(AdminDetailAset $detail): void
    {
        $directory = storage_path('app/public/qrcodes');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $isiQr = (string) $detail->kode_aset;
        $filename = 'qr_aset_' . $detail->aset_id . '_' . md5($isiQr) . '.svg';

        foreach (glob($directory . '/qr_aset_' . $detail->aset_id . '_*.svg') ?: [] as $old) {
            File::delete($old);
        }

        $svg = QrCode::format('svg')->margin(1)->size(300)->generate($isiQr);
        File::put($directory . '/' . $filename, $svg);
    }

    public function generateSingleQr($id)
    {
        try {
            $detail = AdminDetailAset::where('aset_id', $id)->firstOrFail();
            $this->simpanQr($detail);

            return response()->json(['success' => true, 'message' => 'QR Code berhasil dibuat.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat QR! Penyebab: ' . $e->getMessage()]);
        }
    }

    public function generateAllQr()
    {
        try {
            $berhasil = 0;
            $directory = storage_path('app/public/qrcodes');

            AdminDetailAset::chunk(200, function ($asets) use (&$berhasil, $directory) {
                foreach ($asets as $aset) {
                    $file = $directory . '/qr_aset_' . $aset->aset_id . '_' . md5((string) $aset->kode_aset) . '.svg';
                    if (!file_exists($file)) {
                        $this->simpanQr($aset);
                        $berhasil++;
                    }
                }
            });

            return response()->json(['success' => true, 'message' => "Berhasil memproses {$berhasil} label QR."]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
    }
}
