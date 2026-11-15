<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailAset extends Model
{
    use HasFactory;

    protected $table = 'detail_aset';
    
    protected $primaryKey = 'aset_id'; 

    public static bool $autoSinkronUnit = true;

    private static array $kelompokSebelumUpdate = [];

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
        static::updating(function (DetailAset $detail) {
            if (!self::$autoSinkronUnit) {
                return;
            }
            if ($detail->isDirty(['item_id', 'cabang_id', 'Ruangan'])) {
                self::$kelompokSebelumUpdate[$detail->getKey()] = [
                    $detail->getOriginal('item_id'),
                    $detail->getOriginal('cabang_id'),
                    $detail->getOriginal('Ruangan'),
                ];
            }
        });

        static::saved(function (DetailAset $detail) {
            if (!self::$autoSinkronUnit) {
                return;
            }
            self::sinkronkanUnitKelompok($detail->item_id, $detail->cabang_id, $detail->Ruangan);

            $key = $detail->getKey();
            if (isset(self::$kelompokSebelumUpdate[$key])) {
                $lama = self::$kelompokSebelumUpdate[$key];
                unset(self::$kelompokSebelumUpdate[$key]);
                self::sinkronkanUnitKelompok($lama[0], $lama[1], $lama[2]);
            }
        });

        static::deleted(function (DetailAset $detail) {
            if (!self::$autoSinkronUnit) {
                return;
            }
            self::sinkronkanUnitKelompok($detail->item_id, $detail->cabang_id, $detail->Ruangan);
        });
    }

    public static function normalisasiRuangan($ruangan): string
    {
        $teks = trim((string) ($ruangan ?? ''));
        return ($teks === '' || $teks === '-') ? '-' : $teks;
    }

    public static function sinkronkanUnitKelompok($itemId, $cabangId, $ruangan): void
    {
        if (empty($itemId)) {
            return;
        }

        $ruangNorm = self::normalisasiRuangan($ruangan);

        $query = static::query()->where('item_id', $itemId);
        if ($cabangId !== null && $cabangId !== '') {
            $query->where('cabang_id', $cabangId);
        } else {
            $query->whereNull('cabang_id');
        }

        if ($ruangNorm !== '-') {
            $query->whereRaw('TRIM(COALESCE(Ruangan, "")) = ?', [$ruangNorm]);
        } else {
            $query->where(function ($q) {
                $q->whereNull('Ruangan')
                  ->orWhereRaw('TRIM(Ruangan) = ""')
                  ->orWhereRaw('TRIM(Ruangan) = "-"');
            });
        }

        $total = (int) (clone $query)->count();
        if ($total > 0) {
            (clone $query)->update(['unit' => $total]);
        }
    }

    public static function sinkronkanSemuaUnit(): int
    {
        $semua = static::query()->get(['aset_id', 'item_id', 'cabang_id', 'Ruangan', 'unit']);
        $kelompok = [];

        foreach ($semua as $row) {
            $ruangNorm = self::normalisasiRuangan($row->Ruangan);
            $kunci = $row->item_id . '|' . ($row->cabang_id ?? 'null') . '|' . ($ruangNorm === '-' ? '__NO_ROOM__' : mb_strtolower($ruangNorm));
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