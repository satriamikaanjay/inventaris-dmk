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
        // 1. Buat tabel master 'items'
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            
            // ---> TAMBAHKAN DUA BARIS INI DI SINI <---
            $table->foreignId('kategori_id')->nullable()->constrained('kategoris')->onDelete('set null');
            $table->integer('urutan')->default(1);
            
            $table->string('nama_aset');
            $table->string('awal_serial');
            $table->string('warna_seri')->default('#3b82f6');
            $table->timestamps();
        });

        // 2. Tambahkan relasi ke tabel 'detail_aset'
        Schema::table('detail_aset', function (Blueprint $table) {
            // Tambahkan item_id sebagai foreign key
            $table->foreignId('item_id')->nullable()->after('aset_id')->constrained('items')->onDelete('cascade');
            
            // Hapus kolom nama_aset di detail karena sudah dipindah ke tabel master items
            $table->dropColumn('nama_aset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_aset', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropColumn('item_id');
            $table->string('nama_aset')->nullable();
        });

        Schema::dropIfExists('items');
    }
};