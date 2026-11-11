<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('kategori_id')->nullable()->constrained('kategoris')->onDelete('set null');
            $table->integer('urutan')->default(1);
            
            $table->string('nama_aset');
            $table->string('awal_serial');
            $table->string('warna_seri')->default('#3b82f6');
            $table->timestamps();
        });

        Schema::table('detail_aset', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->after('aset_id')->constrained('items')->onDelete('cascade');
            
            $table->dropColumn('nama_aset');
        });
    }

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