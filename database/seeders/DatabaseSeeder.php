<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Shop;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. DATA KATEGORI PROMOSI
        $categories = [
            ['name' => 'Makanan Berat', 'slug' => 'makanan-berat'],
            ['name' => 'Jajanan & Snack', 'slug' => 'jajanan-snack'],
            ['name' => 'Minuman', 'slug' => 'minuman'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // 2. AKUN SUPER ADMIN
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@siswamart.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'whatsapp_number' => '081234567890',
        ]);

        // 3. AKUN PENJUAL (Siswa Wirausaha) - Disimpan ke variabel $seller
        $seller = User::create([
            'name' => 'Dwi (Penjual)',
            'email' => 'Dwi@siswamart.com',
            'password' => Hash::make('password123'),
            'role' => 'penjual',
            'whatsapp_number' => '081234567890',
        ]);

        // 4. DATA LAPAK/USAHA SISWA
        $shop = Shop::create([
            'user_id' => $seller->id, // Sekarang $seller->id sudah terdefinisi!
            'name' => 'Usaha Snack Dwi',
            'description' => 'Menyediakan jajanan hits dan kekinian di area SMKN 11 Bandung.',
        ]);

        // 5. DATA DUMMY PRODUK SISWA
        $snackCategory = Category::where('slug', 'jajanan-snack')->first();
        
        Product::create([
            'shop_id' => $shop->id,
            'category_id' => $snackCategory->id,
            'name' => 'Pangsit Chili Oil',
            'description' => 'Pangsit rebus dengan bumbu chili oil super pedas dan gurih. Cocok buat nemenin ngoding!',
            'price' => 5000.00,
            'stock_status' => 'ready',
            'views_count' => 150,
        ]);
    }
}