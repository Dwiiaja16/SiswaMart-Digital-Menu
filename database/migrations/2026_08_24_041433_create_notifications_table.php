<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Produk yang memicu notifikasi
            $table->string('type')->default('produk_baru'); // Jenis notifikasi, siap dikembangkan nanti
            $table->boolean('is_read')->default(false); // Titik merah (false) / hijau (true)
            $table->timestamp('read_at')->nullable(); // Waktu admin klik/membaca notifikasi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};