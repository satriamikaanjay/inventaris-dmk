<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('detail_aset', function (Blueprint $table) {
        $table->date('tanggal_pengadaan')->nullable()->change();
        $table->date('servis_terakhir')->nullable()->change();
        $table->date('servis_berikutnya')->nullable()->change();
        $table->date('tanggal_mutasi')->nullable()->change();
        $table->date('tanggal_peleburan')->nullable()->change();
        $table->string('lampiran')->nullable()->change();
    });
}

public function down(): void
{
    // opsional
}
};
