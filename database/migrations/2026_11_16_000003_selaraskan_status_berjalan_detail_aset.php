<?php

use App\Models\DetailAset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('detail_aset')) {
            return;
        }

        $rows = DB::table('detail_aset')->get(['aset_id', 'status_aset', 'status_berjalan']);
        foreach ($rows as $row) {
            $selaras = DetailAset::validasiAtauSelaraskanTipeAset($row->status_aset, $row->status_berjalan, true);
            if ($selaras !== null && $selaras !== (string) $row->status_berjalan) {
                DB::table('detail_aset')
                    ->where('aset_id', $row->aset_id)
                    ->update(['status_berjalan' => $selaras]);
            }
        }
    }

    public function down(): void
    {
    }
};
