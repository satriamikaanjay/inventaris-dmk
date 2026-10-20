<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_aset', function (Blueprint $table) {
            // Menambahkan kolom lampiran
            $table->text('lampiran')->nullable()->after('nilai_jual');
        });
    }

    public function down(): void
    {
        Schema::table('detail_aset', function (Blueprint $table) {
            // Menghapus kolom jika di-rollback
            $table->dropColumn('lampiran');
        });
    }
};