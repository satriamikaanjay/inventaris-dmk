<?php

use App\Models\DetailAset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('detail_aset')) {
            DetailAset::sinkronkanSemuaUnit();
        }
    }

    public function down(): void
    {
    }
};
