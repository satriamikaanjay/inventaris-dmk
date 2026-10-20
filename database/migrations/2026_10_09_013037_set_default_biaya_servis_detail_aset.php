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
        $table->decimal('biaya_servis', 15, 2)->default(0)->change();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_aset', function (Blueprint $table) {
            //
        });
    }
};
