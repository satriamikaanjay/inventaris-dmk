<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\DetailAset;
use App\Models\StatusAset;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    private const BATAS_WAKTUNYA  = 7;
    private const BATAS_MENDEKATI = 30;
    private const PER_HALAMAN     = 20;

    public function index(Request $request)
    {
        $hariIni = Carbon::today();

        $tipe = in_array($request->query('tipe'), ['terlewat', 'waktunya', 'mendekati', 'rusak'], true)
            ? $request->query('tipe')
            : 'semua';

        $dasar = $this->queryDasar($request);

        $hitung = [];
        foreach (['semua', 'terlewat', 'waktunya', 'mendekati', 'rusak'] as $jenis) {
            $hitung[$jenis] = $this->rentang($dasar, $jenis, $hariIni)->count();
        }

        $notifs = $this->rentang($dasar, $tipe, $hariIni)
            ->with(['item', 'kategori', 'cabang'])
            ->orderBy('servis_berikutnya')      
            ->orderBy('aset_id')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        $notifs->getCollection()->transform(function ($aset) use ($hariIni) {
            $selisih = null;
            $jenis_servis = null;

            if ($aset->servis_berikutnya) {
                $tanggal = Carbon::parse($aset->servis_berikutnya)->startOfDay();
                $selisih = (int) $hariIni->diffInDays($tanggal, false);   
                
                if ($selisih <= self::BATAS_MENDEKATI) {
                    $jenis_servis = $selisih < 0
                        ? 'terlewat'
                        : ($selisih <= self::BATAS_WAKTUNYA ? 'waktunya' : 'mendekati');
                }
            }

            $aset->selisih = $selisih;
            $aset->jenis_servis = $jenis_servis;
            
            $aset->is_rusak = stripos($aset->status_aset, 'Rusak') !== false;

            return $aset;
        });

        return view('masteradmin.notifikasi', [
            'notifs'   => $notifs,
            'hitung'   => $hitung,
            'tipe'     => $tipe,
            'cabangs'  => Cabang::orderBy('nama_cabang')->get(),
            'hariIni'  => $hariIni,
            'batas'    => ['waktunya' => self::BATAS_WAKTUNYA, 'mendekati' => self::BATAS_MENDEKATI],
            'statuses' => StatusAset::all(),
        ]);
    }

    private function queryDasar(Request $request)
    {
        $query = DetailAset::query();

        if ($request->filled('cabang')) {
            $query->where('cabang_id', $request->query('cabang'));
        }

        if ($request->filled('q')) {
            $kata = trim($request->query('q'));
            $query->where(function ($w) use ($kata) {
                $w->where('kode_aset', 'like', "%{$kata}%")
                  ->orWhere('merk', 'like', "%{$kata}%")
                  ->orWhere('Ruangan', 'like', "%{$kata}%")
                  ->orWhereHas('item', fn ($i) => $i->where('nama_aset', 'like', "%{$kata}%"))
                  ->orWhereHas('cabang', fn ($c) => $c->where('nama_cabang', 'like', "%{$kata}%"));
            });
        }

        return $query;
    }

    private function rentang($query, string $jenis, Carbon $hariIni)
    {
        $q         = clone $query;
        $hari      = $hariIni->toDateString();
        $waktunya  = $hariIni->copy()->addDays(self::BATAS_WAKTUNYA)->toDateString();
        $mendekati = $hariIni->copy()->addDays(self::BATAS_MENDEKATI)->toDateString();

        return match ($jenis) {
            'terlewat'  => $q->whereNotNull('servis_berikutnya')->where('servis_berikutnya', '<', $hari),
            'waktunya'  => $q->whereNotNull('servis_berikutnya')->where('servis_berikutnya', '>=', $hari)->where('servis_berikutnya', '<=', $waktunya),
            'mendekati' => $q->whereNotNull('servis_berikutnya')->where('servis_berikutnya', '>', $waktunya)->where('servis_berikutnya', '<=', $mendekati),
            'rusak'     => $q->where('status_aset', 'like', '%Rusak%'),
            default     => $q->where(function ($sub) use ($hari, $mendekati) {
                $sub->where(function ($s1) use ($mendekati) {
                    $s1->whereNotNull('servis_berikutnya')
                       ->where('servis_berikutnya', '<=', $mendekati);
                })->orWhere('status_aset', 'like', '%Rusak%');
            }),
        };
    }
}