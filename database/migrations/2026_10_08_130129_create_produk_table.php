<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id('id_produk');

            $table->string('username');
            $table->foreign('username')->references('username')->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('id_kategori');
            $table->foreign('id_kategori')->references('id_kategori')->on('kategori')->onDelete('cascade')->onUpdate('cascade');

            $table->string('nama_produk');
            $table->text('deskripsi');
            $table->decimal('harga', 12, 2);
            $table->string('foto_produk')->nullable();
            $table->enum('status_stok', ['ready', 'pre-order'])->default('ready');

            // Penampung tayangan/view produk
            $table->unsignedBigInteger('views')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};