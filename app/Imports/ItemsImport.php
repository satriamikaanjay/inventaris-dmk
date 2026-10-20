<?php

namespace App\Imports;

use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ItemsImport implements ToModel, WithStartRow, WithCalculatedFormulas
{
    public function startRow(): int
    {
        return 2; // Mulai dari baris ke-2 (melewati header)
    }

    private function transformDate($value)
    {
        if (empty($value) || $value === '-' || $value === ' - ' || $value === '?') {
            return '2026-01-01'; 
        }

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return date('Y-m-d', strtotime($value));
        } catch (\Exception $e) {
            return '2026-01-01';
        }
    }

    public function model(array $row): Model|null
    {
        if (empty($row[0])) {
            return null;
        }

        return Item::updateOrCreate(
            ['kode_aset' => $row[0]], 
            [
                'nama_aset'          => $row[1] ?? '-',
                'kategori'           => $row[2] ?? '-',
                'merk'               => $row[3] ?? '-',
                'nomor_seri'         => $row[4] ?? '-',
                'unit'               => isset($row[5]) && is_numeric($row[5]) ? $row[5] : 1,
                'tanggal_pengadaan'  => $this->transformDate($row[6] ?? null),
                'harga_beli'         => isset($row[7]) && is_numeric($row[7]) ? $row[7] : 0,
                'Ruangan'            => $row[8] ?? '-',
                'vendor_pengadaan'   => $row[9] ?? '-',
                'lokasi_saat_ini'    => $row[10] ?? '-',
                'penanggung_jawab'   => $row[11] ?? '-',
                'status_aset'        => $row[12] ?? '-',
                'status_maintenance' => $row[13] ?? '-',
                'servis_terakhir'    => $this->transformDate($row[14] ?? null),
                'biaya_servis'       => isset($row[15]) && is_numeric($row[15]) ? $row[15] : 0,
                'teknisi'            => $row[16] ?? '-',
                'servis_berikutnya'  => $this->transformDate($row[17] ?? null),
                'riwayat_mutasi'     => $row[18] ?? '-',
                'tanggal_mutasi'     => $this->transformDate($row[19] ?? null),
                'status_peleburan'   => $row[20] ?? '-',
                'tanggal_peleburan'  => $this->transformDate($row[21] ?? null),
                'metode_peleburan'   => $row[22] ?? '-',
                'nilai_jual'         => isset($row[23]) && is_numeric($row[23]) ? $row[23] : 0,
                'no_berita_acara'    => $row[24] ?? '-',
            ]
        );
    }
}