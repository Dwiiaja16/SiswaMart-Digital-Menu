<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DataPenjual;
use App\Models\Kategori;
use App\Models\Produk;
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
            ['nama_kategori' => 'Makanan Berat', 'slug' => 'makanan-berat'],
            ['nama_kategori' => 'Jajanan & Snack', 'slug' => 'jajanan-snack'],
            ['nama_kategori' => 'Minuman', 'slug' => 'minuman'],
        ];

        foreach ($categories as $category) {
            Kategori::create($category);
        }

        // 2. AKUN SUPER ADMIN
        $admin = User::create([
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        // 3. AKUN PENJUAL (Siswa Wirausaha)
        $seller = User::create([
            'username' => 'dwi',
            'password' => Hash::make('password123'),
            'role'     => 'penjual',
        ]);

        // 4. DATA PENJUAL (Profil & Usaha Siswa)
        DataPenjual::create([
            'username'            => $seller->username,
            'nama_lengkap'        => 'Dwi',
            'nama_kelas'          => 'XII RPL 1',
            'foto_penjual'        => null,
            'alamat'              => 'SMKN 11 Bandung',
            'created_by_admin_id' => $admin->user_id,
            'email'               => 'dwi@siswamart.com',
            'nomor_whatsapp'      => '081234567890',
        ]);

        // 5. DATA DUMMY PRODUK SISWA
        $snackCategory = Kategori::where('slug', 'jajanan-snack')->first();
        
        Produk::create([
            'username'        => $seller->username,
            'id_kategori'     => $snackCategory->id_kategori,
            'nama_produk'     => 'Pangsit Chili Oil',
            'deskripsi'       => 'Pangsit rebus dengan bumbu chili oil super pedas dan gurih. Cocok buat nemenin ngoding!',
            'harga'           => 5000.00,
            'foto_produk'     => null,
            'status_stok'     => 'ready',
            'status_moderasi' => 'disetujui',
        ]);
    }
}