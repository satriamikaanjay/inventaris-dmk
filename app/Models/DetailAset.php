<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DetailAset extends Model
{
    use HasFactory;

    protected $table = 'detail_aset';
    
    protected $primaryKey = 'aset_id'; 

    public static bool $autoSinkronUnit = true;

    private static array $kelompokSebelumUpdate = [];

    /**
     * Ringkasan jumlah master (item/kategori/cabang) yang ikut terhapus pada pembersihan terakhir.
     *
     * @var array{items: int, kategoris: int, cabangs: int}
     */
    public static array $pembersihanTerakhir = [
        'items' => 0,
        'kategoris' => 0,
        'cabangs' => 0,
    ];

    protected $fillable = [
        'item_id', 
        'kode_aset',
        'kategori_id',
        'cabang_id',
        'merk',
        'nomor_seri',
        'unit',
        'tanggal_pengadaan',
        'harga_beli',
        'Ruangan',
        'vendor_pengadaan',
        'lokasi_saat_ini',
        'penanggung_jawab',
        'status_aset',
        'status_berjalan',
        'status_maintenance',
        'servis_terakhir',
        'biaya_servis',
        'teknisi',
        'servis_berikutnya',
        'riwayat_mutasi',
        'tanggal_mutasi',
        'status_peleburan',
        'tanggal_peleburan',
        'metode_peleburan',
        'nilai_jual',
        'no_berita_acara',
        'lampiran',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    protected static function booted(): void
    {
        static::creating(function (DetailAset $detail) {
            if ($detail->unit === null || (int) $detail->unit < 1) {
                $detail->unit = 1;
            }
        });

        static::updating(function (DetailAset $detail) {
            if (!self::$autoSinkronUnit) {
                return;
            }
            if ($detail->isDirty(['item_id', 'kategori_id', 'cabang_id'])) {
                self::$kelompokSebelumUpdate[$detail->getKey()] = [
                    $detail->getOriginal('item_id'),
                    $detail->getOriginal('kategori_id'),
                    $detail->getOriginal('cabang_id'),
                ];
            }
        });

        static::saved(function (DetailAset $detail) {
            if (!self::$autoSinkronUnit) {
                return;
            }
            self::sinkronkanUnitKelompok($detail->item_id, $detail->kategori_id, $detail->cabang_id);

            $key = $detail->getKey();
            if (isset(self::$kelompokSebelumUpdate[$key])) {
                $lama = self::$kelompokSebelumUpdate[$key];
                unset(self::$kelompokSebelumUpdate[$key]);
                self::sinkronkanUnitKelompok($lama[0], $lama[1], $lama[2]);
            }
        });

        static::deleted(function (DetailAset $detail) {
            if (self::$autoSinkronUnit) {
                self::sinkronkanUnitKelompok($detail->item_id, $detail->kategori_id, $detail->cabang_id);
            }

            self::$pembersihanTerakhir = self::bersihkanIndukTanpaDetail(
                [$detail->item_id],
                [$detail->kategori_id],
                [$detail->cabang_id]
            );
        });
    }

    /**
     * Hapus master Item, Kategori, dan Cabang yang tidak lagi dipakai di tabel detail_aset.
     *
     * @return array{items: int, kategoris: int, cabangs: int}
     */
    public static function bersihkanIndukTanpaDetail(
        array $itemIds = [],
        array $kategoriIds = [],
        array $cabangIds = []
    ): array {
        $normalisasiId = static function (array $ids): array {
            $hasil = [];
            foreach ($ids as $id) {
                if ($id === null || $id === '') {
                    continue;
                }
                $intId = (int) $id;
                if ($intId > 0) {
                    $hasil[$intId] = $intId;
                }
            }
            return array_values($hasil);
        };

        $kandidatItem = $normalisasiId($itemIds);
        $kandidatKategori = $normalisasiId($kategoriIds);
        $kandidatCabang = $normalisasiId($cabangIds);

        $ringkasan = [
            'items' => 0,
            'kategoris' => 0,
            'cabangs' => 0,
        ];

        if (empty($kandidatItem) && empty($kandidatKategori) && empty($kandidatCabang)) {
            return $ringkasan;
        }

        // Hapus Item lebih dulu sebelum cek Kategori agar relasi kategori pada item terlepas bersih
        foreach ($kandidatItem as $itemId) {
            if (!static::query()->where('item_id', $itemId)->exists()) {
                $item = Item::find($itemId);
                if ($item) {
                    if (!empty($item->kategori_id)) {
                        $kandidatKategori[] = (int) $item->kategori_id;
                    }
                    $ringkasan['items'] += Item::whereKey($itemId)->delete();
                }
            }
        }

        foreach ($normalisasiId($kandidatKategori) as $kategoriId) {
            $masihDipakaiDetail = static::query()->where('kategori_id', $kategoriId)->exists();
            $masihDipakaiViaItem = Item::query()
                ->where('kategori_id', $kategoriId)
                ->whereHas('details')
                ->exists();

            if (!$masihDipakaiDetail && !$masihDipakaiViaItem) {
                $ringkasan['kategoris'] += Kategori::whereKey($kategoriId)->delete();
            }
        }

        foreach ($kandidatCabang as $cabangId) {
            if (!static::query()->where('cabang_id', $cabangId)->exists()) {
                $ringkasan['cabangs'] += Cabang::whereKey($cabangId)->delete();
            }
        }

        return $ringkasan;
    }

    /**
     * Hapus massal DetailAset sekaligus sinkronkan unit kelompok dan bersihkan master tanpa detail.
     *
     * @return array{deleted: int, kodes: array<int, string>, cleanup: array{items: int, kategoris: int, cabangs: int}}
     */
    public static function hapusMassalDanBersihkan(array $ids): array
    {
        $idsBersih = array_values(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $ids
        ), fn (string $v) => $v !== ''));

        if (empty($idsBersih)) {
            return [
                'deleted' => 0,
                'kodes' => [],
                'cleanup' => ['items' => 0, 'kategoris' => 0, 'cabangs' => 0],
            ];
        }

        return DB::transaction(function () use ($idsBersih) {
            $inst = new static();
            $pk = $inst->getKeyName();
            $rows = static::query()
                ->whereIn($pk, $idsBersih)
                ->get([$pk, 'kode_aset', 'item_id', 'kategori_id', 'cabang_id', 'Ruangan']);

            if ($rows->isEmpty()) {
                return [
                    'deleted' => 0,
                    'kodes' => [],
                    'cleanup' => ['items' => 0, 'kategoris' => 0, 'cabangs' => 0],
                ];
            }

            $kodes = [];
            $itemIds = [];
            $kategoriIds = [];
            $cabangIds = [];
            $kelompok = [];

            foreach ($rows as $row) {
                if (!empty($row->kode_aset)) {
                    $kodes[] = (string) $row->kode_aset;
                }
                if (!empty($row->item_id)) {
                    $itemIds[] = (int) $row->item_id;
                }
                if (!empty($row->kategori_id)) {
                    $kategoriIds[] = (int) $row->kategori_id;
                }
                if (!empty($row->cabang_id)) {
                    $cabangIds[] = (int) $row->cabang_id;
                }

                $kunci = self::kunciKelompokUnit($row->item_id, $row->kategori_id, $row->cabang_id);
                $kelompok[$kunci] = [$row->item_id, $row->kategori_id, $row->cabang_id];
            }

            $deletedCount = static::query()->whereIn($pk, $rows->pluck($pk)->all())->delete();

            foreach ($kelompok as $grp) {
                self::sinkronkanUnitKelompok($grp[0], $grp[1], $grp[2]);
            }

            $cleanup = self::bersihkanIndukTanpaDetail($itemIds, $kategoriIds, $cabangIds);
            self::$pembersihanTerakhir = $cleanup;

            return [
                'deleted' => $deletedCount,
                'kodes' => $kodes,
                'cleanup' => $cleanup,
            ];
        });
    }

    /**
     * Format ringkasan penghapusan master otomatis untuk pesan notifikasi.
     *
     * @param array{items?: int, kategoris?: int, cabangs?: int} $cleanup
     */
    public static function formatRingkasanPembersihan(array $cleanup): string
    {
        $bagian = [];
        if (($cleanup['items'] ?? 0) > 0) {
            $bagian[] = $cleanup['items'] . ' nama aset';
        }
        if (($cleanup['kategoris'] ?? 0) > 0) {
            $bagian[] = $cleanup['kategoris'] . ' kategori';
        }
        if (($cleanup['cabangs'] ?? 0) > 0) {
            $bagian[] = $cleanup['cabangs'] . ' cabang';
        }

        return empty($bagian)
            ? ''
            : ' (' . implode(', ', $bagian) . ' yang tidak terpakai ikut terhapus)';
    }

    public static function normalisasiRuangan($ruangan): string
    {
        $teks = trim((string) ($ruangan ?? ''));
        return ($teks === '' || $teks === '-') ? '-' : $teks;
    }

    /**
     * Daftar Tipe Aset (status_berjalan) yang diizinkan berdasarkan Status Aset Umum.
     *
     * @return array<int, string>
     */
    public static function tipeAsetDiizinkan(?string $statusAset): array
    {
        if (mb_strtolower(trim((string) $statusAset), 'UTF-8') === 'baik') {
            return ['Aset Berjalan'];
        }

        return ['Aset Tidak Berjalan', 'Aset Tidak Dipakai'];
    }

    /**
     * Menyeragamkan penulisan Tipe Aset (status_berjalan).
     */
    public static function normalisasiTipeAset(?string $statusBerjalan): string
    {
        $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $statusBerjalan)), 'UTF-8');

        return match ($norm) {
            'aset berjalan'       => 'Aset Berjalan',
            'aset tidak berjalan' => 'Aset Tidak Berjalan',
            'aset tidak dipakai'  => 'Aset Tidak Dipakai',
            default               => trim((string) $statusBerjalan),
        };
    }

    /**
     * Memvalidasi atau menyelaraskan Tipe Aset agar konsisten dengan Status Aset Umum.
     * Status "Baik" hanya boleh "Aset Berjalan"; status selain "Baik" hanya boleh "Aset Tidak Berjalan" atau "Aset Tidak Dipakai".
     */
    public static function validasiAtauSelaraskanTipeAset(?string $statusAset, ?string $statusBerjalan, bool $paksaSelaras = false): ?string
    {
        $diizinkan = self::tipeAsetDiizinkan($statusAset);
        $nilaiNorm = self::normalisasiTipeAset($statusBerjalan);

        if (in_array($nilaiNorm, $diizinkan, true)) {
            return $nilaiNorm;
        }

        return $paksaSelaras ? $diizinkan[0] : null;
    }

    /**
     * Membentuk kunci pengelompokan unit berdasarkan item, kategori, dan cabang (tanpa memisahkan ruangan).
     */
    private static function kunciKelompokUnit($itemId, $kategoriId, $cabangId): string
    {
        $itemKey = ($itemId !== null && $itemId !== '') ? (string) (int) $itemId : 'null';
        $katKey  = ($kategoriId !== null && $kategoriId !== '') ? (string) (int) $kategoriId : 'null';
        $cabKey  = ($cabangId !== null && $cabangId !== '') ? (string) (int) $cabangId : 'null';

        return $itemKey . '|' . $katKey . '|' . $cabKey;
    }

    /**
     * Menyelaraskan kolom unit untuk aset dengan nama aset & kategori yang sama di satu cabang.
     */
    public static function sinkronkanUnitKelompok($itemId, $kategoriId, $cabangId): void
    {
        $query = static::query();

        if ($itemId !== null && $itemId !== '') {
            $query->where('item_id', (int) $itemId);
        } else {
            $query->whereNull('item_id');
        }

        if ($kategoriId !== null && $kategoriId !== '') {
            $query->where('kategori_id', (int) $kategoriId);
        } else {
            $query->whereNull('kategori_id');
        }

        if ($cabangId !== null && $cabangId !== '') {
            $query->where('cabang_id', (int) $cabangId);
        } else {
            $query->whereNull('cabang_id');
        }

        $total = (int) (clone $query)->count();
        if ($total > 0) {
            (clone $query)
                ->where(function ($q) use ($total) {
                    $q->whereNull('unit')
                      ->orWhere('unit', '!=', $total);
                })
                ->update(['unit' => $total]);
        }
    }

    /**
     * Menghitung ulang kolom unit seluruh detail_aset per nama aset & kategori di masing-masing cabang.
     */
    public static function sinkronkanSemuaUnit(): int
    {
        $semua = static::query()->get(['aset_id', 'item_id', 'kategori_id', 'cabang_id', 'unit']);
        $kelompok = [];

        foreach ($semua as $row) {
            $kunci = self::kunciKelompokUnit($row->item_id, $row->kategori_id, $row->cabang_id);
            $kelompok[$kunci][] = $row;
        }

        $diperbarui = 0;
        foreach ($kelompok as $daftar) {
            $total = count($daftar);
            $idsUntukUpdate = [];
            foreach ($daftar as $item) {
                if ((int) $item->unit !== $total) {
                    $idsUntukUpdate[] = $item->aset_id;
                }
            }
            if (!empty($idsUntukUpdate)) {
                static::query()->whereIn('aset_id', $idsUntukUpdate)->update(['unit' => $total]);
                $diperbarui += count($idsUntukUpdate);
            }
        }

        return $diperbarui;
    }
}