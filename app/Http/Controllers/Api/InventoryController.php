<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DetailAset;
use App\Models\Kategori;
use App\Models\Cabang;

class InventoryController extends Controller
{
    public function index()
    {
        try {
            $asetList = DetailAset::with(['item', 'kategori', 'cabang'])->get();

            // Kumpulkan daftar ruangan unik per cabang (hanya ruangan valid selain '-')
            $ruanganPerCabang = [];
            foreach ($asetList as $row) {
                $namaCab = $row->cabang ? trim((string) $row->cabang->nama_cabang) : '';
                $namaRuang = trim((string) ($row->Ruangan ?? ''));
                if ($namaCab !== '' && $namaCab !== '-' && $namaRuang !== '' && $namaRuang !== '-') {
                    $keyCab = mb_strtolower($namaCab);
                    if (!isset($ruanganPerCabang[$keyCab])) {
                        $ruanganPerCabang[$keyCab] = [];
                    }
                    if (!in_array($namaRuang, $ruanganPerCabang[$keyCab], true)) {
                        $ruanganPerCabang[$keyCab][] = $namaRuang;
                    }
                }
            }

            $mappedAset = $asetList->map(function ($data) use ($ruanganPerCabang) {
                $namaCabang = $data->cabang ? ($data->cabang->nama_cabang ?: '-') : '-';
                $keyCab = mb_strtolower(trim((string) $namaCabang));
                $daftarRuanganCabang = $ruanganPerCabang[$keyCab] ?? [];
                sort($daftarRuanganCabang);

                $ruanganVal = trim((string) ($data->Ruangan ?? ''));
                if ($ruanganVal === '') {
                    $ruanganVal = '-';
                }

                $lokasiSaatIniVal = trim((string) ($data->lokasi_saat_ini ?? ''));
                if ($lokasiSaatIniVal === '') {
                    $lokasiSaatIniVal = '-';
                }

                return [
                    'id'                   => $data->aset_id,
                    'kode_aset'            => $data->kode_aset ?: '-',
                    'nama_aset'            => $data->item ? ($data->item->nama_aset ?: '-') : '-',
                    'kategori'             => $data->kategori ? ($data->kategori->nama_kategori ?: '-') : '-',
                    'kategori_id'          => $data->kategori_id,
                    'cabang'               => $namaCabang,
                    'cabang_id'            => $data->cabang_id,
                    'ruangan'              => $ruanganVal,
                    'ruangan_cabang'       => array_values($daftarRuanganCabang),
                    'merk'                 => $data->merk ?: '-',
                    'nomor_seri'           => $data->nomor_seri ?: '-',
                    'unit'                 => $data->unit ?: 0,
                    'tanggal_pengadaan'    => $data->tanggal_pengadaan ?: '-',
                    'harga_beli'           => $data->harga_beli ? (float) $data->harga_beli : 0.0,
                    'vendor_pengadaan'     => $data->vendor_pengadaan ?: '-',
                    'no_berita_acara'      => $data->no_berita_acara ?: '-',
                    'lokasi'               => ($ruanganVal !== '-') ? $ruanganVal : $lokasiSaatIniVal,
                    'lokasi_saat_ini'      => $lokasiSaatIniVal,
                    'penanggung_jawab'     => $data->penanggung_jawab ?: '-',
                    'status'               => $data->status_aset ?: '-',
                    'status_raw'           => $data->status_aset ?: '-',
                    'status_berjalan'      => $data->status_berjalan ?: '-',
                    'status_maintenance'   => $data->status_maintenance ?: '-',
                    'teknisi'              => $data->teknisi ?: '-',
                    'biaya_servis'         => $data->biaya_servis ? (float) $data->biaya_servis : 0.0,
                    'servis_terakhir'      => $data->servis_terakhir ?: '-',
                    'servis_berikutnya'    => $data->servis_berikutnya ?: '-',
                    'riwayat_mutasi'       => $data->riwayat_mutasi ?: '-',
                    'tanggal_mutasi'       => $data->tanggal_mutasi ?: '-',
                    'status_peleburan'     => $data->status_peleburan ?: '-',
                    'tanggal_peleburan'    => $data->tanggal_peleburan ?: '-',
                    'metode_peleburan'     => $data->metode_peleburan ?: '-',
                    'nilai_jual'           => $data->nilai_jual ? (float) $data->nilai_jual : 0.0,
                    'spesifikasi'          => ($data->merk ?: '-') . ' | Seri: ' . ($data->nomor_seri ?: '-'),
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data berhasil diambil',
                'total'   => $mappedAset->count(),
                'data'    => $mappedAset
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data inventaris.',
                'total'   => 0,
                'data'    => []
            ], 500);
        }
    }

    public function categories()
    {
        try {
            $kategori = Kategori::select('id', 'nama_kategori')->get();

            return response()->json([
                'status' => 'success',
                'data'   => $kategori
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'data' => []], 500);
        }
    }

    public function branches()
    {
        try {
            $cabangList = Cabang::select('id', 'nama_cabang')->get();
            $asetRooms = DetailAset::select('cabang_id', 'Ruangan')
                ->whereNotNull('Ruangan')
                ->get()
                ->groupBy('cabang_id');

            $data = $cabangList->map(function ($cab) use ($asetRooms) {
                $rows = $asetRooms->get($cab->id, collect());
                $rooms = [];
                foreach ($rows as $r) {
                    $rm = trim((string) ($r->Ruangan ?? ''));
                    if ($rm !== '' && $rm !== '-' && !in_array($rm, $rooms, true)) {
                        $rooms[] = $rm;
                    }
                }
                sort($rooms);
                return [
                    'id'          => $cab->id,
                    'nama_cabang' => $cab->nama_cabang,
                    'ruangan'     => array_values($rooms),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data'   => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'data' => []], 500);
        }
    }
}
