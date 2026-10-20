<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_aset', function (Blueprint $table) {
            // Menambahkan kolom status_berjalan
            $table->string('status_berjalan')->nullable()->default('Tidak Ada')->after('status_aset'); 
        });
    }

    public function down(): void
    {
        Schema::table('detail_aset', function (Blueprint $table) {
            $table->dropColumn('status_berjalan');
        });
    }
};