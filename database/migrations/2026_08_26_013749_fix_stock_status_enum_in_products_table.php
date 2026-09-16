<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN stock_status ENUM('ready', 'pre_order', 'out_of_stock') NOT NULL DEFAULT 'ready'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN stock_status ENUM('ready', 'pre-order') NOT NULL DEFAULT 'ready'");
    }
};