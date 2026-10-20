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
        $table->dropUnique('aset_kode_aset_unique');
        $table->index('kode_aset'); // tetap cepat saat dicari
    });
}

public function down(): void
{
    Schema::table('detail_aset', function (Blueprint $table) {
        $table->dropIndex(['kode_aset']);
        $table->unique('kode_aset', 'aset_kode_aset_unique');
    });
}
};
