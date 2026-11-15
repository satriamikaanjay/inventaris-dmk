<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\DetailAset;
use App\Models\RiwayatAktivitas;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPengajuanController extends Controller
{
    public const JENIS_VALID = [
        'peminjaman',
        'permintaan',
        'peleburan',
        'perpindahan',
        'servis',
    ];

    public const PREFIX_NOMOR = [
        'peminjaman'  => 'PJN',
        'permintaan'  => 'PRM',
        'peleburan'   => 'PLB',
        'perpindahan' => 'PRP',
        'servis'      => 'SRV',
    ];

    public const LABEL_JENIS = [
        'peminjaman'  => 'Pengajuan Peminjaman',
        'permintaan'  => 'Pengajuan Permintaan',
        'peleburan'   => 'Pengajuan Peleburan',
        'perpindahan' => 'Pengajuan Perpindahan',
        'servis'      => 'Pengajuan Servis',
    ];

    public static function pastikanTabelTersedia(): void
    {
        try {
            if (!Schema::hasTable('pengajuan_asets')) {
                Schema::create('pengajuan_asets', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('nomor_pengajuan', 60)->unique();
                    $table->string('jenis', 30)->index();
                    $table->unsignedBigInteger('user_id')->nullable()->index();
                    $table->unsignedBigInteger('aset_id')->nullable()->index();
                    $table->string('kode_aset', 120)->nullable();
                    $table->string('nama_aset', 255)->nullable();
                    $table->string('kategori_nama', 120)->nullable();
                    $table->integer('unit_jumlah')->default(1);
                    $table->string('pemohon', 150)->nullable();
                    $table->string('divisi', 120)->nullable();
                    $table->string('cabang_asal', 120)->nullable();
                    $table->string('ruangan_asal', 120)->nullable();
                    $table->unsignedBigInteger('cabang_tujuan_id')->nullable();
                    $table->string('cabang_tujuan', 120)->nullable();
                    $table->string('ruangan_tujuan', 120)->nullable();
                    $table->string('penanggung_jawab_baru', 150)->nullable();
                    $table->date('tanggal_pengajuan')->nullable();
                    $table->date('tanggal_selesai')->nullable();
                    $table->string('metode_peleburan', 120)->nullable();
                    $table->decimal('nilai_estimasi', 15, 2)->default(0);
                    $table->string('no_berita_acara', 120)->nullable();
                    $table->string('teknisi', 150)->nullable();
                    $table->string('prioritas', 40)->default('Normal');
                    $table->text('keterangan')->nullable();
                    $table->string('lampiran', 255)->nullable();
                    $table->string('lampiran_nama', 255)->nullable();
                    $table->text('catatan_admin')->nullable();
                    $table->string('sumber', 50)->default('User Mobile');
                    $table->string('status', 40)->default('Menunggu')->index();
                    $table->timestamps();
                });
            } else {
                Schema::table('pengajuan_asets', function (Blueprint $table) {
                    if (!Schema::hasColumn('pengajuan_asets', 'user_id')) {
                        $table->unsignedBigInteger('user_id')->nullable()->index()->after('jenis');
                    }
                    if (!Schema::hasColumn('pengajuan_asets', 'lampiran')) {
                        $table->string('lampiran', 255)->nullable()->after('keterangan');
                    }
                    if (!Schema::hasColumn('pengajuan_asets', 'lampiran_nama')) {
                        $table->string('lampiran_nama', 255)->nullable()->after('lampiran');
                    }
                });
            }
        } catch (\Throwable $e) {
        }
    }

    public static function formatRowUntukWeb(object $r): array
    {
        $lampiranFile = !empty($r->lampiran) ? (string) $r->lampiran : '';
        $lampiranUrl = $lampiranFile !== '' ? route('pengajuan.lampiran', ['filename' => $lampiranFile]) : '';
        $lampiranDownloadUrl = $lampiranFile !== '' ? route('pengajuan.lampiran', ['filename' => $lampiranFile, 'download' => 1]) : '';
        $tglFormatted = '-';
        if (!empty($r->tanggal_pengajuan)) {
            try {
                $tglFormatted = \Carbon\Carbon::parse($r->tanggal_pengajuan)->format('d/m/Y');
            } catch (\Throwable $e) {
                $tglFormatted = (string) $r->tanggal_pengajuan;
            }
        }
        $tglSelesaiFormatted = '-';
        if (!empty($r->tanggal_selesai)) {
            try {
                $tglSelesaiFormatted = \Carbon\Carbon::parse($r->tanggal_selesai)->format('d/m/Y');
            } catch (\Throwable $e) {
                $tglSelesaiFormatted = (string) $r->tanggal_selesai;
            }
        }

        return [
            'id'                    => (int) $r->id,
            'nomor_pengajuan'       => (string) ($r->nomor_pengajuan ?? '-'),
            'jenis'                 => strtolower((string) ($r->jenis ?? 'peminjaman')),
            'jenis_label'           => self::LABEL_JENIS[strtolower((string) ($r->jenis ?? 'peminjaman'))] ?? 'Pengajuan Aset',
            'user_id'               => $r->user_id ? (int) $r->user_id : null,
            'aset_id'               => $r->aset_id ? (int) $r->aset_id : null,
            'kode_aset'             => (string) ($r->kode_aset ?? '-'),
            'nama_aset'             => (string) ($r->nama_aset ?? '-'),
            'kategori_nama'         => (string) ($r->kategori_nama ?? '-'),
            'unit_jumlah'           => (int) ($r->unit_jumlah ?? 1),
            'pemohon'               => (string) ($r->pemohon ?? '-'),
            'divisi'                => (string) ($r->divisi ?? '-'),
            'cabang_asal'           => (string) ($r->cabang_asal ?? '-'),
            'ruangan_asal'          => (string) ($r->ruangan_asal ?? '-'),
            'cabang_tujuan_id'      => $r->cabang_tujuan_id ? (int) $r->cabang_tujuan_id : null,
            'cabang_tujuan'         => (string) ($r->cabang_tujuan ?? '-'),
            'ruangan_tujuan'        => (string) ($r->ruangan_tujuan ?? '-'),
            'penanggung_jawab_baru' => (string) ($r->penanggung_jawab_baru ?? '-'),
            'tanggal_pengajuan'     => (string) ($r->tanggal_pengajuan ?? ''),
            'tanggal_formatted'     => $tglFormatted,
            'tanggal_selesai'       => (string) ($r->tanggal_selesai ?? ''),
            'tanggal_selesai_fmt'   => $tglSelesaiFormatted,
            'metode_peleburan'      => (string) ($r->metode_peleburan ?? '-'),
            'nilai_estimasi'        => (float) ($r->nilai_estimasi ?? 0),
            'nilai_estimasi_fmt'    => 'Rp ' . number_format((float) ($r->nilai_estimasi ?? 0), 0, ',', '.'),
            'no_berita_acara'       => (string) ($r->no_berita_acara ?? '-'),
            'teknisi'               => (string) ($r->teknisi ?? '-'),
            'prioritas'             => (string) ($r->prioritas ?? 'Normal'),
            'keterangan'            => (string) ($r->keterangan ?? '-'),
            'lampiran'              => $lampiranFile,
            'lampiran_nama'         => (string) ($r->lampiran_nama ?? ($lampiranFile ?: '')),
            'lampiran_url'          => $lampiranUrl,
            'lampiran_download_url' => $lampiranDownloadUrl,
            'catatan_admin'         => (string) ($r->catatan_admin ?? ''),
            'sumber'                => (string) ($r->sumber ?? 'User Mobile'),
            'status'                => (string) ($r->status ?? 'Menunggu'),
            'status_url'            => route('pengajuan.status', $r->id),
        ];
    }

    public static function hitungRingkasanPending(): array
    {
        self::pastikanTabelTersedia();
        $perJenis = [
            'peminjaman'  => 0,
            'permintaan'  => 0,
            'peleburan'   => 0,
            'perpindahan' => 0,
            'servis'      => 0,
        ];
        $totalPending = 0;
        try {
            if (Schema::hasTable('pengajuan_asets')) {
                $rows = DB::table('pengajuan_asets')
                    ->select('jenis', DB::raw('COUNT(*) as jml'))
                    ->where('status', 'Menunggu')
                    ->groupBy('jenis')
                    ->get();
                foreach ($rows as $r) {
                    $j = strtolower((string) $r->jenis);
                    if (isset($perJenis[$j])) {
                        $perJenis[$j] = (int) $r->jml;
                        $totalPending += (int) $r->jml;
                    }
                }
            }
        } catch (\Throwable $e) {
        }
        return [
            'total_pending' => $totalPending,
            'per_jenis'     => $perJenis,
        ];
    }

    public function index(Request $request)
    {
        self::pastikanTabelTersedia();

        $pengajuanList = collect();
        try {
            if (Schema::hasTable('pengajuan_asets')) {
                $pengajuanList = DB::table('pengajuan_asets')
                    ->orderByDesc('id')
                    ->get();
            }
        } catch (\Throwable $e) {
            $pengajuanList = collect();
        }

        if ($request->has('_poll_pengajuan_rt')) {
            $formatted = $pengajuanList->map(fn($r) => self::formatRowUntukWeb($r))->values();
            $ringkasan = self::hitungRingkasanPending();
            return response()->json([
                'success'       => true,
                'total_semua'   => $pengajuanList->count(),
                'total_menunggu'=> $pengajuanList->where('status', 'Menunggu')->count(),
                'total_acc'     => $pengajuanList->whereIn('status', ['Disetujui', 'Selesai'])->count(),
                'total_ditolak' => $pengajuanList->where('status', 'Ditolak')->count(),
                'pending_jenis' => $ringkasan['per_jenis'],
                'items'         => $formatted,
            ]);
        }

        return view('admin.pengajuan', compact('pengajuanList'));
    }

    public function updateStatus(Request $request, $id)
    {
        self::pastikanTabelTersedia();

        $row = DB::table('pengajuan_asets')->where('id', $id)->first();
        if (!$row) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Data pengajuan tidak ditemukan.'], 404);
            }
            return redirect()->back()->withErrors(['Data pengajuan tidak ditemukan.']);
        }

        $statusBaru = ucfirst(strtolower(trim((string) $request->input('status', 'Disetujui'))));
        if (!in_array($statusBaru, ['Menunggu', 'Disetujui', 'Ditolak', 'Selesai'], true)) {
            $statusBaru = 'Disetujui';
        }

        $catatanAdmin = trim((string) $request->input('catatan_admin', $row->catatan_admin ?? ''));

        DB::table('pengajuan_asets')->where('id', $id)->update([
            'status'        => $statusBaru,
            'catatan_admin' => $catatanAdmin !== '' ? $catatanAdmin : $row->catatan_admin,
            'updated_at'    => now(),
        ]);

        if (in_array($statusBaru, ['Disetujui', 'Selesai'], true) && !empty($row->aset_id)) {
            $aset = DetailAset::find($row->aset_id);
            if ($aset) {
                $jenis = strtolower((string) $row->jenis);
                $tglAksi = $row->tanggal_pengajuan ?: date('Y-m-d');

                if ($jenis === 'perpindahan') {
                    $updateAset = [
                        'Ruangan'        => DetailAset::normalisasiRuangan($row->ruangan_tujuan),
                        'riwayat_mutasi' => 'Mutasi dari ' . ($row->cabang_asal ?: '-') . ' (' . ($row->ruangan_asal ?: '-') . ') ke ' . ($row->cabang_tujuan ?: '-') . ' (' . ($row->ruangan_tujuan ?: '-') . ') [' . $row->nomor_pengajuan . ']',
                        'tanggal_mutasi' => $tglAksi,
                    ];
                    if (!empty($row->cabang_tujuan_id)) {
                        $updateAset['cabang_id'] = $row->cabang_tujuan_id;
                    }
                    if (!empty($row->cabang_tujuan) && $row->cabang_tujuan !== '-') {
                        $updateAset['lokasi_saat_ini'] = $row->cabang_tujuan;
                    }
                    if (!empty($row->penanggung_jawab_baru) && $row->penanggung_jawab_baru !== '-') {
                        $updateAset['penanggung_jawab'] = $row->penanggung_jawab_baru;
                    }
                    $aset->update($updateAset);
                } elseif ($jenis === 'peleburan') {
                    $aset->update([
                        'status_peleburan'  => 'Dilebur (' . $row->nomor_pengajuan . ')',
                        'tanggal_peleburan' => $tglAksi,
                        'metode_peleburan'  => $row->metode_peleburan ?: 'Dilebur',
                        'nilai_jual'        => (int) round((float) ($row->nilai_estimasi ?? 0)),
                        'no_berita_acara'   => ($row->no_berita_acara && $row->no_berita_acara !== '-') ? $row->no_berita_acara : $aset->no_berita_acara,
                        'status_aset'       => 'Rusak Berat',
                        'status_berjalan'   => 'Aset Tidak Berjalan',
                    ]);
                } elseif ($jenis === 'servis') {
                    $updateServis = [
                        'teknisi'      => ($row->teknisi && $row->teknisi !== '-') ? $row->teknisi : $aset->teknisi,
                        'biaya_servis' => (float) ($row->nilai_estimasi ?? $aset->biaya_servis ?? 0),
                    ];
                    if ($statusBaru === 'Selesai') {
                        $updateServis['status_maintenance'] = 'Selesai Servis';
                        $updateServis['status_aset'] = 'Baik';
                        $updateServis['servis_terakhir'] = date('Y-m-d');
                        if (!empty($row->tanggal_selesai)) {
                            $updateServis['servis_berikutnya'] = $row->tanggal_selesai;
                        }
                    } else {
                        $updateServis['status_maintenance'] = 'Sedang Servis (' . $row->nomor_pengajuan . ')';
                    }
                    $aset->update($updateServis);
                } elseif ($jenis === 'peminjaman') {
                    if ($statusBaru === 'Selesai') {
                        $aset->update([
                            'status_aset' => 'Baik',
                        ]);
                    } else {
                        $aset->update([
                            'status_aset'      => 'Dipinjam',
                            'penanggung_jawab' => ($row->pemohon && $row->pemohon !== '-') ? $row->pemohon : $aset->penanggung_jawab,
                        ]);
                    }
                }
            }
        }

        $labelAksi = $statusBaru === 'Disetujui' ? 'Menyetujui (ACC)' : ($statusBaru === 'Ditolak' ? 'Menolak' : 'Menyelesaikan');
        $this->catatRiwayat(
            $labelAksi . ' ' . (self::LABEL_JENIS[$row->jenis] ?? 'Pengajuan') . ' dari ' . ($row->pemohon ?: 'User') . ' (' . $row->nomor_pengajuan . ')',
            'Pengajuan'
        );

        if ($request->wantsJson() || $request->ajax() || $request->is('api/*')) {
            $updatedRow = DB::table('pengajuan_asets')->where('id', $id)->first();
            $ringkasan = self::hitungRingkasanPending();
            return response()->json([
                'success'       => true,
                'message'       => 'Pengajuan ' . $row->nomor_pengajuan . ' berhasil diperbarui menjadi ' . ($statusBaru === 'Disetujui' ? 'Diterima (ACC)' : $statusBaru) . '.',
                'item'          => $updatedRow ? self::formatRowUntukWeb($updatedRow) : null,
                'pending_jenis' => $ringkasan['per_jenis'],
                'total_pending' => $ringkasan['total_pending'],
            ]);
        }

        return redirect()
            ->route('pengajuan.index', ['jenis' => $row->jenis])
            ->with('success', 'Pengajuan ' . $row->nomor_pengajuan . ' berhasil di-' . ($statusBaru === 'Disetujui' ? 'ACC (Disetujui)' : $statusBaru) . '!');
    }

    public function apiIndex(Request $request)
    {
        self::pastikanTabelTersedia();

        $query = DB::table('pengajuan_asets')->orderByDesc('id');
        if ($request->filled('jenis')) {
            $query->where('jenis', strtolower(trim((string) $request->input('jenis'))));
        }
        if ($request->filled('status')) {
            $query->where('status', trim((string) $request->input('status')));
        }
        if ($request->filled('pemohon')) {
            $query->where('pemohon', 'like', '%' . trim((string) $request->input('pemohon')) . '%');
        }

        $rows = $query->get()->map(fn($r) => $this->mapRowForMobile($r));

        return response()->json([
            'status' => 'success',
            'data'   => $rows,
        ]);
    }

    public function apiUser(Request $request)
    {
        self::pastikanTabelTersedia();

        $userId = $request->input('user_id');
        $namaUser = trim((string) $request->input('nama_user', ''));
        $firstWord = strtolower(explode(' ', $namaUser)[0] ?? '');

        $query = DB::table('pengajuan_asets')->orderByDesc('id');

        if ((!empty($userId) && (int) $userId > 0) || $namaUser !== '') {
            $query->where(function ($q) use ($userId, $namaUser, $firstWord) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', 0);
                if (!empty($userId) && (int) $userId > 0) {
                    $q->orWhere('user_id', (int) $userId);
                }
                if ($namaUser !== '') {
                    $q->orWhereRaw('LOWER(TRIM(pemohon)) = ?', [mb_strtolower($namaUser)])
                      ->orWhere('pemohon', 'like', '%' . $namaUser . '%');
                }
                if ($firstWord !== '' && strlen($firstWord) >= 3) {
                    $q->orWhere('pemohon', 'like', $firstWord . '%');
                }
            });
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            $rows = DB::table('pengajuan_asets')->orderByDesc('id')->get();
        }
        $mapped = $rows->map(fn($r) => $this->mapRowForMobile($r))->values();

        $pending = $mapped->filter(function ($item) {
            $st = strtolower((string) ($item['status'] ?? 'menunggu'));
            return $st === 'menunggu' || $st === 'pending';
        })->values();

        return response()->json([
            'status'        => 'success',
            'total_pending' => $pending->count(),
            'total_riwayat' => $mapped->count(),
            'pending'       => $pending,
            'riwayat'       => $mapped,
        ]);
    }

    public function apiStore(Request $request)
    {
        self::pastikanTabelTersedia();

        $rawJenis = $request->input('jenis', $request->input('jenis_pengajuan', 'peminjaman'));
        $jenis = strtolower(trim((string) $rawJenis));
        $jenis = str_replace('pengajuan ', '', $jenis);

        if (!in_array($jenis, self::JENIS_VALID, true)) {
            return response()->json([
                'status'  => 'error',
                'success' => false,
                'message' => 'Jenis pengajuan tidak valid. Gunakan: peminjaman, permintaan, peleburan, perpindahan, atau servis.',
            ], 422);
        }

        $prefix = self::PREFIX_NOMOR[$jenis] ?? 'PGJ';
        $tglKode = date('Ymd');
        $urutan = ((int) DB::table('pengajuan_asets')->max('id')) + 1;
        $nomorPengajuan = sprintf('%s-%s-%04d', $prefix, $tglKode, $urutan);

        $asetObj = null;
        if ($request->filled('aset_id')) {
            $asetObj = DetailAset::with(['item', 'kategori', 'cabang'])->find($request->input('aset_id'));
        }
        if (!$asetObj && $request->filled('kode_aset')) {
            $cleanKode = trim(str_replace('Kode:', '', (string) $request->input('kode_aset')));
            $asetObj = DetailAset::with(['item', 'kategori', 'cabang'])
                ->where('kode_aset', $cleanKode)
                ->first();
        }

        $cabangTujuanObj = null;
        if ($request->filled('cabang_tujuan_id')) {
            $cabangTujuanObj = Cabang::find($request->input('cabang_tujuan_id'));
        } elseif ($request->filled('cabang_tujuan')) {
            $cabangTujuanObj = Cabang::where('nama_cabang', trim((string) $request->input('cabang_tujuan')))->first();
        }

        $kodeAset = $asetObj ? $asetObj->kode_aset : trim(str_replace('Kode:', '', (string) $request->input('kode_aset', '-')));
        $namaAset = $asetObj
            ? ($asetObj->item->nama_aset ?? '-')
            : trim((string) $request->input('nama_aset', '-'));
        $kategoriNama = $asetObj
            ? ($asetObj->kategori->nama_kategori ?? '-')
            : trim((string) $request->input('kategori', $request->input('kategori_nama', '-')));
        $cabangAsal = $asetObj
            ? ($asetObj->cabang->nama_cabang ?? ($asetObj->lokasi_saat_ini ?? '-'))
            : trim((string) $request->input('cabang_asal', '-'));
        $ruanganAsal = $request->filled('ruangan_asal')
            ? DetailAset::normalisasiRuangan($request->input('ruangan_asal'))
            : ($asetObj
                ? DetailAset::normalisasiRuangan($asetObj->Ruangan)
                : DetailAset::normalisasiRuangan($request->input('lokasi', '-')));

        $cabangTujuanNama = $cabangTujuanObj
            ? $cabangTujuanObj->nama_cabang
            : trim((string) $request->input('cabang_tujuan', $cabangAsal));
        $ruanganTujuan = DetailAset::normalisasiRuangan($request->input('ruangan_tujuan', $ruanganAsal));

        if ($jenis === 'perpindahan') {
            if ($ruanganTujuan === '-' || $ruanganTujuan === '') {
                return response()->json([
                    'status'  => 'error',
                    'success' => false,
                    'message' => 'Ruangan tujuan baru wajib dipilih untuk pengajuan perpindahan.',
                ], 422);
            }
        }

        $savedFileName = null;
        $originalFileName = null;

        $dirLampiran = public_path('lampiran_pengajuan');
        if (!is_dir($dirLampiran)) {
            @mkdir($dirLampiran, 0775, true);
        }

        $fileObj = null;
        foreach (['file_lampiran', 'lampiran', 'file', 'foto', 'image', 'bukti', 'attachment'] as $fieldKey) {
            if ($request->hasFile($fieldKey)) {
                $fileObj = $request->file($fieldKey);
                break;
            }
        }

        $rawB64Input = '';
        foreach (['file_base64', 'lampiran_base64', 'foto_base64', 'image_base64'] as $b64Key) {
            if ($request->filled($b64Key)) {
                $rawB64Input = (string) $request->input($b64Key);
                break;
            }
        }

        if ($fileObj) {
            $uploadError = \App\Support\FileUploadSecurity::validateUploadedFile($fileObj);
            if ($uploadError !== null) {
                return response()->json([
                    'status'  => 'error',
                    'success' => false,
                    'message' => $uploadError,
                ], 422);
            }
            $originalFileName = $fileObj->getClientOriginalName();
            $ext = strtolower((string) $fileObj->getClientOriginalExtension());
            $savedFileName = \App\Support\FileUploadSecurity::generateSafeFilename($ext);
            $fileObj->move($dirLampiran, $savedFileName);
        } elseif ($rawB64Input !== '') {
            $rawB64 = $rawB64Input;
            if (str_contains($rawB64, ',')) {
                $rawB64 = substr($rawB64, strpos($rawB64, ',') + 1);
            }
            $rawB64 = preg_replace('/\s+/', '', $rawB64);
            $decodedBytes = base64_decode((string) $rawB64, true);
            $originalFileName = trim((string) $request->input('file_name', $request->input('lampiran_nama', 'lampiran_pengajuan.jpg'))) ?: 'lampiran_pengajuan.jpg';

            if ($decodedBytes === false || $decodedBytes === '') {
                return response()->json([
                    'status'  => 'error',
                    'success' => false,
                    'message' => 'Format lampiran base64 tidak valid atau kosong.',
                ], 422);
            }

            $uploadError = \App\Support\FileUploadSecurity::validateRawUpload($originalFileName, $decodedBytes);
            if ($uploadError !== null) {
                return response()->json([
                    'status'  => 'error',
                    'success' => false,
                    'message' => $uploadError,
                ], 422);
            }

            $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
            $savedFileName = \App\Support\FileUploadSecurity::generateSafeFilename($ext);
            @file_put_contents($dirLampiran . DIRECTORY_SEPARATOR . $savedFileName, $decodedBytes);
        }

        $pemohonNama = trim((string) $request->input('pemohon', $request->input('nama_user', 'User Mobile'))) ?: 'User Mobile';
        $userIdVal = $request->filled('user_id') && (int) $request->input('user_id') > 0
            ? (int) $request->input('user_id')
            : null;

        $unitJumlah = max(1, (int) $request->input('unit_jumlah', 1));
        $nilaiEstimasi = max(0, (float) preg_replace('/[^0-9.]/', '', (string) $request->input('nilai_estimasi', 0)));

        $keteranganInput = trim((string) $request->input('keterangan', '-')) ?: '-';

        $dataSimpan = [
            'nomor_pengajuan'       => $nomorPengajuan,
            'jenis'                 => $jenis,
            'user_id'               => $userIdVal,
            'aset_id'               => $asetObj ? $asetObj->aset_id : null,
            'kode_aset'             => $kodeAset !== '' ? $kodeAset : '-',
            'nama_aset'             => $namaAset !== '' ? $namaAset : '-',
            'kategori_nama'         => $kategoriNama !== '' ? $kategoriNama : '-',
            'unit_jumlah'           => $unitJumlah,
            'pemohon'               => $pemohonNama,
            'divisi'                => trim((string) $request->input('divisi', '-')) ?: '-',
            'cabang_asal'           => $cabangAsal !== '' ? $cabangAsal : '-',
            'ruangan_asal'          => $ruanganAsal,
            'cabang_tujuan_id'      => $cabangTujuanObj ? $cabangTujuanObj->id : ($asetObj ? $asetObj->cabang_id : null),
            'cabang_tujuan'         => $cabangTujuanNama !== '' ? $cabangTujuanNama : '-',
            'ruangan_tujuan'        => $ruanganTujuan,
            'penanggung_jawab_baru' => trim((string) $request->input('penanggung_jawab_baru', '-')) ?: '-',
            'tanggal_pengajuan'     => $request->filled('tanggal_pengajuan') ? $request->input('tanggal_pengajuan') : date('Y-m-d'),
            'tanggal_selesai'       => $request->filled('tanggal_selesai') ? $request->input('tanggal_selesai') : null,
            'metode_peleburan'      => trim((string) $request->input('metode_peleburan', '-')) ?: '-',
            'nilai_estimasi'        => $nilaiEstimasi,
            'no_berita_acara'       => trim((string) $request->input('no_berita_acara', '-')) ?: '-',
            'teknisi'               => trim((string) $request->input('teknisi', '-')) ?: '-',
            'prioritas'             => trim((string) $request->input('prioritas', 'Normal')) ?: 'Normal',
            'keterangan'            => $keteranganInput,
            'lampiran'              => $savedFileName,
            'lampiran_nama'         => $originalFileName,
            'catatan_admin'         => null,
            'sumber'                => 'User Mobile',
            'status'                => 'Menunggu',
            'created_at'            => now(),
            'updated_at'            => now(),
        ];

        $idBaru = DB::table('pengajuan_asets')->insertGetId($dataSimpan);
        $rowObj = (object) array_merge(['id' => $idBaru], $dataSimpan);

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => (self::LABEL_JENIS[$jenis] ?? 'Pengajuan') . ' berhasil dikirim (' . $nomorPengajuan . ').',
            'data'    => $this->mapRowForMobile($rowObj),
        ], 201);
    }

    public function viewLampiran(Request $request, $filename)
    {
        $cleanName = basename(rawurldecode((string) $filename));
        $candidates = [
            public_path('lampiran_pengajuan' . DIRECTORY_SEPARATOR . $cleanName),
            storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'lampiran_pengajuan' . DIRECTORY_SEPARATOR . $cleanName),
            public_path('lampiran_aset' . DIRECTORY_SEPARATOR . $cleanName),
        ];

        $filePath = null;
        foreach ($candidates as $cand) {
            if (is_file($cand)) {
                $filePath = $cand;
                break;
            }
        }

        if (!$filePath) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'bmp'  => 'image/bmp',
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';
        $safeInlineExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'];
        $canInline = in_array($ext, $safeInlineExts, true) && !$request->boolean('download');
        $fileSize = @filesize($filePath);
        $disposition = $canInline ? 'inline' : 'attachment';

        $headers = [
            'Content-Type'            => $mime,
            'Content-Disposition'     => $disposition . '; filename="' . $cleanName . '"',
            'X-Content-Type-Options'  => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox;",
            'Cache-Control'           => 'private, max-age=3600',
        ];
        if ($fileSize !== false && $fileSize > 0) {
            $headers['Content-Length'] = (string) $fileSize;
        }

        return response()->stream(function () use ($filePath) {
            $stream = @fopen($filePath, 'rb');
            if ($stream !== false) {
                while (!feof($stream)) {
                    echo fread($stream, 65536);
                    flush();
                }
                fclose($stream);
            } else {
                $content = @file_get_contents($filePath);
                if ($content !== false) {
                    echo $content;
                }
            }
        }, 200, $headers);
    }

    private function mapRowForMobile(object $r): array
    {
        $jenis = strtolower((string) ($r->jenis ?? 'peminjaman'));
        $ruangAsal = $r->ruangan_asal ?? '-';
        $ruangTujuan = $r->ruangan_tujuan ?? '-';
        $cabAsal = $r->cabang_asal ?? '-';

        $lokasiDisplay = $jenis === 'perpindahan'
            ? "Ruang Lama: {$ruangAsal} → Ruang Baru: {$ruangTujuan} ({$cabAsal})"
            : ($ruangAsal !== '-' ? "{$cabAsal} - {$ruangAsal}" : $cabAsal);

        $lampiranUrl = !empty($r->lampiran)
            ? url('/api/pengajuan/lampiran/' . rawurlencode($r->lampiran))
            : null;

        return [
            'id'                => (int) $r->id,
            'nomor_pengajuan'   => $r->nomor_pengajuan ?? '-',
            'user_id'           => isset($r->user_id) ? (int) $r->user_id : null,
            'nama_user'         => $r->pemohon ?? 'User Mobile',
            'kode_aset'         => $r->kode_aset ?? '-',
            'nama_aset'         => $r->nama_aset ?? '-',
            'kategori'          => $r->kategori_nama ?? '-',
            'lokasi'            => $lokasiDisplay,
            'cabang_asal'       => $cabAsal,
            'ruangan_asal'      => $ruangAsal,
            'cabang_tujuan'     => $r->cabang_tujuan ?? $cabAsal,
            'ruangan_tujuan'    => $ruangTujuan,
            'jenis_pengajuan'   => $jenis,
            'keterangan'        => $r->keterangan ?? '-',
            'lampiran'          => $r->lampiran ?? null,
            'lampiran_nama'     => $r->lampiran_nama ?? ($r->lampiran ?? null),
            'lampiran_url'      => $lampiranUrl,
            'status'            => $r->status ?? 'Menunggu',
            'alasan_penolakan'  => $r->catatan_admin ?? null,
            'tanggal_pengajuan' => $r->tanggal_pengajuan ?? substr((string) ($r->created_at ?? date('Y-m-d')), 0, 10),
            'tanggal_respon'    => isset($r->updated_at) ? substr((string) $r->updated_at, 0, 16) : null,
            'respon_oleh'       => in_array(strtolower((string) ($r->status ?? '')), ['disetujui', 'ditolak', 'selesai'], true) ? 'Admin' : null,
        ];
    }

    private function catatRiwayat(string $aktivitas, string $modul = 'Pengajuan'): void
    {
        try {
            if (class_exists(RiwayatAktivitas::class)) {
                RiwayatAktivitas::create([
                    'user_id'   => Auth::id(),
                    'aktivitas' => $aktivitas,
                    'modul'     => $modul,
                    'status'    => 'Berhasil',
                ]);
            }
        } catch (\Throwable $e) {
        }
    }
}
