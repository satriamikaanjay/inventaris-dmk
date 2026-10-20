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
        // Mengubah nama tabel dari 'aset' menjadi 'detail_aset'
        Schema::rename('aset', 'detail_aset');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mengembalikan nama tabel dari 'detail_aset' ke 'aset' (jika di-rollback)
        Schema::rename('detail_aset', 'aset');
    }
};
