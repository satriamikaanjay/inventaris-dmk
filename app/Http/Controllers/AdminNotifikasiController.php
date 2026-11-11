<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminDetailAset;
use App\Models\AdminCabang;
use App\Models\AdminStatusAset;
use Carbon\Carbon;

class AdminNotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $hariIni = Carbon::today();
        $batasWaktunya = 7;
        $batasMendekati = 30;

        $tipe = in_array($request->query('tipe'), ['terlewat', 'waktunya', 'mendekati', 'rusak'], true)
            ? $request->query('tipe')
            : 'semua';

        $dasar = $this->queryDasar($request);

        $hitung = [];
        foreach (['semua', 'terlewat', 'waktunya', 'mendekati', 'rusak'] as $jenis) {
            $hitung[$jenis] = $this->rentang($dasar, $jenis, $hariIni, $batasWaktunya, $batasMendekati)->count();
        }

        $notifs = $this->rentang($dasar, $tipe, $hariIni, $batasWaktunya, $batasMendekati)
            ->with(['item', 'kategori', 'cabang'])
            ->orderBy('servis_berikutnya')
            ->orderBy('aset_id')
            ->paginate(20)
            ->withQueryString();

        $notifs->getCollection()->transform(function ($aset) use ($hariIni, $batasWaktunya, $batasMendekati) {
            $selisih = null;
            $jenis_servis = null;

            if ($aset->servis_berikutnya) {
                $tanggal = Carbon::parse($aset->servis_berikutnya)->startOfDay();
                $selisih = (int) $hariIni->diffInDays($tanggal, false);

                if ($selisih <= $batasMendekati) {
                    $jenis_servis = $selisih < 0
                        ? 'terlewat'
                        : ($selisih <= $batasWaktunya ? 'waktunya' : 'mendekati');
                }
            }

            $aset->selisih = $selisih;
            $aset->jenis_servis = $jenis_servis;
            $aset->is_rusak = stripos((string) $aset->status_aset, 'Rusak') !== false;

            return $aset;
        });

        $cabangs = AdminCabang::orderBy('nama_cabang')->get();
        $statuses = AdminStatusAset::all();
        $batas = ['waktunya' => $batasWaktunya, 'mendekati' => $batasMendekati];

        return view('admin.notifikasi', compact('notifs', 'cabangs', 'statuses', 'hitung', 'batas', 'hariIni', 'tipe'));
    }

    private function queryDasar(Request $request)
    {
        $query = AdminDetailAset::query();

        if ($request->filled('cabang')) {
            $query->where('cabang_id', $request->query('cabang'));
        }

        if ($request->filled('q')) {
            $kata = trim((string) $request->query('q'));
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

    private function rentang($query, string $jenis, Carbon $hariIni, int $batasWaktunya, int $batasMendekati)
    {
        $q = clone $query;
        $hari = $hariIni->toDateString();
        $waktunya = $hariIni->copy()->addDays($batasWaktunya)->toDateString();
        $mendekati = $hariIni->copy()->addDays($batasMendekati)->toDateString();

        return match ($jenis) {
            'terlewat'  => $q->whereNotNull('servis_berikutnya')->where('servis_berikutnya', '<', $hari),
            'waktunya'  => $q->whereNotNull('servis_berikutnya')->where('servis_berikutnya', '>=', $hari)->where('servis_berikutnya', '<=', $waktunya),
            'mendekati' => $q->whereNotNull('servis_berikutnya')->where('servis_berikutnya', '>', $waktunya)->where('servis_berikutnya', '<=', $mendekati),
            'rusak'     => $q->where('status_aset', 'like', '%Rusak%'),
            default     => $q->where(function ($sub) use ($mendekati) {
                $sub->where(function ($s1) use ($mendekati) {
                    $s1->whereNotNull('servis_berikutnya')
                       ->where('servis_berikutnya', '<=', $mendekati);
                })->orWhere('status_aset', 'like', '%Rusak%');
            }),
        };
    }
}