<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_cabang')->unique();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // PERBAIKAN: Nama tabel diubah menjadi 'detail_aset'
        Schema::table('detail_aset', function (Blueprint $table) {
            $table->unsignedBigInteger('cabang_id')->nullable()->after('kategori_id');
        });
    }

    public function down(): void
    {
        // PERBAIKAN: Nama tabel diubah menjadi 'detail_aset'
        Schema::table('detail_aset', function (Blueprint $table) {
            $table->dropColumn('cabang_id');
        });
        Schema::dropIfExists('cabangs');
    }
};