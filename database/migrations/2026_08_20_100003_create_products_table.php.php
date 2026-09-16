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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete(); // Relasi ke tabel shops
            $table->foreignId('category_id')->constrained()->cascadeOnDelete(); // Relasi ke kategori
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2); // Untuk filter Termurah
            $table->string('image_path')->nullable();
            $table->enum('stock_status', ['ready', 'pre-order'])->default('ready');
            $table->unsignedBigInteger('views_count')->default(0); // Untuk filter Paling Banyak Dilihat
            $table->timestamps(); // Menyimpan created_at untuk filter Terbaru
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};