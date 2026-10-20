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
    Schema::create('aset', function (Blueprint $table) {
        $table->id('aset_id'); // Primary Key barang
        $table->string('kode_aset')->unique();
        $table->string('nama_aset');
        $table->foreignId('kategori_id')->nullable()->constrained('kategoris')->nullOnDelete();
        $table->string('merk');
        $table->string('nomor_seri');
        $table->integer('unit');
        $table->date('tanggal_pengadaan');
        $table->integer('harga_beli');
        $table->string('Ruangan');
        $table->string('vendor_pengadaan');
        $table->string('lokasi_saat_ini');
        $table->string('penanggung_jawab');
        $table->string('status_aset');
        $table->string('status_maintenance')->nullable();
        $table->date('servis_terakhir');
        $table->integer('biaya_servis');
        $table->string('teknisi');
        $table->date('servis_berikutnya');
        $table->string('riwayat_mutasi')->nullable();
        $table->date('tanggal_mutasi')->nullable();
        $table->string('status_peleburan')->nullable();
        $table->date('tanggal_peleburan')->nullable();
        $table->string('metode_peleburan')->nullable();
        $table->integer('nilai_jual')->nullable();
        $table->string('no_berita_acara')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
