<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE data_penjual MODIFY status_toko ENUM('buka', 'tutup', 'suspended') NOT NULL DEFAULT 'buka'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE data_penjual MODIFY status_toko ENUM('buka', 'tutup') NOT NULL DEFAULT 'buka'");
    }
};
