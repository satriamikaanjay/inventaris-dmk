<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_aktivitas', function (Blueprint $table) {
            // Ini akan otomatis membuat kolom 'deleted_at' bertipe timestamp
            $table->softDeletes(); 
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_aktivitas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};