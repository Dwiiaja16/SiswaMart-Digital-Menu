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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Relasi ke produk yang direview
            $table->string('reviewer_name'); // Nama pembeli/siswa yang mengulas
            $table->unsignedTinyInteger('rating'); // Bintang 1 - 5
            $table->text('comment'); // Isi ulasan / kritik & saran
            $table->boolean('is_approved')->default(true); // Default true (langsung tampil)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};