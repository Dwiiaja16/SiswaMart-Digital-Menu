<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ambil semua produk lama yang masih punya category_id
        $products = DB::table('products')->whereNotNull('category_id')->get();

        foreach ($products as $product) {
            // Cek apakah relasi di tabel pivot category_product sudah ada
            $exists = DB::table('category_product')
                ->where('product_id', $product->id)
                ->where('category_id', $product->category_id)
                ->exists();

            // Jika belum ada, masukkan data kategori lamanya ke tabel pivot
            if (!$exists) {
                DB::table('category_product')->insert([
                    'product_id'  => $product->id,
                    'category_id' => $product->category_id,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Kosongkan tabel pivot jika migration di-rollback
        DB::table('category_product')->truncate();
    }
};