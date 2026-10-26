<?php

use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\PenjualController;
use App\Http\Controllers\Admin\RekapController;
use App\Http\Controllers\Admin\ModerasiProdukController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Penjual\ProdukController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\Penjual\LaporanController;
use App\Http\Controllers\UlasanController;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Ulasan;
use App\Models\User;
use App\Models\DataPenjual;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| 1. ROUTE PUBLIK (Dapat Diakses Siapa Pun)
|--------------------------------------------------------------------------
*/

// Katalog & Detail Produk Publik
Route::get('/', [KatalogController::class, 'index'])->name('catalog.index');
Route::get('/kategori', [KatalogController::class, 'categories'])->name('catalog.categories');
Route::get('/catalog/product/{product}', [KatalogController::class, 'show'])->name('products.show');

// Halaman Informasi Publik
Route::get('/cara-memesan', fn () => Inertia::render('CaraMemesan/Index'))->name('how-to-order');
Route::get('/tentang-kami', fn () => Inertia::render('TentangKami/Index'))->name('about');

// Ulasan Produk Publik
Route::post('/products/{product}/reviews', [UlasanController::class, 'store'])->name('reviews.store');

// Google OAuth
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);


/*
|--------------------------------------------------------------------------
| 2. ROUTE TERPROTEKSI (Wajib Login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // === PINTU GERBANG DASHBOARD UTAMA ===
    Route::get('/dashboard', function () {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'penjual') {
            // HANYA tendang jika akun toko BENAR-BENAR di-suspend oleh Admin
            if ($user->dataPenjual?->status_toko === 'suspended') {
                Auth::guard('web')->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'suspended' => 'Akun toko kamu sedang ditangguhkan (suspend) oleh Admin. Silakan hubungi pengelola SiswaMart untuk informasi lebih lanjut.',
                ]);
            }

            return redirect()->route('penjual.dashboard');
        }

        return redirect()->route('catalog.index');
    })->name('dashboard');


    // === FITUR KHUSUS PENJUAL (Siswa) ===
    Route::middleware(['role:penjual'])->prefix('penjual')->name('penjual.')->group(function () {
        
        // Dashboard Penjual
        Route::get('/dashboard', function () {
            $user = Auth::user();

            // Proteksi Lanjutan: Hanya tendang jika status 'suspended' oleh Admin
            if ($user->dataPenjual?->status_toko === 'suspended') {
                Auth::guard('web')->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'suspended' => 'Akun toko Anda sedang ditangguhkan (suspend) oleh Admin. Silakan hubungi pengelola SiswaMart.',
                ]);
            }

            $productIds = Produk::where('username', $user->username)->pluck('id_produk');

            return Inertia::render('Penjual/Dashboard', [
                'seller' => $user->dataPenjual,
                'stats'  => [
                    'total_products'     => $productIds->count(),
                    'ready_count'        => Produk::where('username', $user->username)->where('status_stok', 'ready')->count(),
                    'pre_order_count'    => Produk::where('username', $user->username)->where('status_stok', 'pre-order')->count(),
                    'total_reviews'      => Ulasan::whereIn('id_produk', $productIds)->count(),
                    'avg_rating'         => Ulasan::whereIn('id_produk', $productIds)->avg('rating')
                        ? number_format(Ulasan::whereIn('id_produk', $productIds)->avg('rating'), 1)
                        : '0.0',
                ],
                'recentProducts' => Produk::where('username', $user->username)->with('kategori')->latest()->take(4)->get(),
                'recentReviews'  => Ulasan::whereIn('id_produk', $productIds)
                    ->with('produk')
                    ->latest()
                    ->take(4)
                    ->get(),
            ]);
        })->name('dashboard');

        // Toggle Status Toko (Buka / Tutup) oleh Penjual Mandiri
        Route::post('/shop/toggle-status', function () {
            $user = Auth::user();

            $seller = DataPenjual::firstOrCreate(
                ['username' => $user->username],
                [
                    'nama_lengkap' => $user->name ?? $user->username,
                    'nama_toko'    => 'Toko ' . $user->username,
                    'email'        => $user->email ?? null,
                    'status_toko'  => 'buka',
                ]
            );

            // Cegah penjual mengubah status jika tokonya di-suspend oleh admin
            if ($seller->status_toko === 'suspended') {
                return redirect()->back()->with('error', 'Toko Anda sedang ditangguhkan oleh Admin.');
            }

            $seller->status_toko = ($seller->status_toko === 'buka') ? 'tutup' : 'buka';
            $seller->save();

            return redirect()->back()->with('success', 'Status toko berhasil diperbarui!');
        })->name('shop.toggle-status');

        // Kelola Produk Penjual
        Route::get('/products', [ProdukController::class, 'index'])->name('products.index');
        Route::post('/products', [ProdukController::class, 'store'])->name('products.store');
        Route::match(['post', 'put', 'patch'], '/products/{product}', [ProdukController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProdukController::class, 'destroy'])->name('products.destroy');

        // Laporan Performa Penjual
        Route::get('/reports', [LaporanController::class, 'index'])->name('reports.index');
    });

    // Ulasan Penjual
    Route::get('/reviews', [UlasanController::class, 'index'])->name('reviews.index');


    // === FITUR KHUSUS ADMIN ===
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {

        // Dashboard Admin
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard', [
                'stats' => [
                    'totalSellers'    => User::where('role', 'penjual')->count(),
                    'activeShops'     => DataPenjual::where('status_toko', 'buka')->count(),
                    'totalProducts' => Produk::where('status_moderasi', 'disetujui')->count(),
                    'totalCategories' => Kategori::count(),
                ],
                'recentSellers' => User::where('role', 'penjual')
                    ->with('dataPenjual')
                    ->latest()
                    ->take(5)
                    ->get(),
            ]);
        })->name('dashboard');

        // Manajemen Kategori
        Route::resource('categories', KategoriController::class)->except(['create', 'edit', 'show']);

        // Manajemen Penjual
        Route::post('/sellers/{seller}', [PenjualController::class, 'update'])->name('sellers.update');
        Route::resource('sellers', PenjualController::class)->except(['create', 'edit', 'show']);

        // Moderasi Produk Admin
        Route::get('/products', [ModerasiProdukController::class, 'index'])->name('products.index');
        Route::patch('/products/{product}/approve', [ModerasiProdukController::class, 'approve'])->name('products.approve');
        Route::patch('/products/{product}/reject', [ModerasiProdukController::class, 'reject'])->name('products.reject');
        Route::delete('/products/{product}', [ModerasiProdukController::class, 'destroy'])->name('products.destroy');

        // Rekapitulasi Penjual & Toggle Suspend
        Route::get('/recap', [RekapController::class, 'index'])->name('recap');
        Route::patch('/recap/{username}/toggle-suspend', [RekapController::class, 'toggleSuspend'])->name('recap.toggle-suspend');

        // Notifikasi Admin
        Route::get('/notifications', [NotifikasiController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/{notification}/read', [NotifikasiController::class, 'markAsRead'])->name('notifications.read');

        // Moderasi Ulasan Admin
        Route::get('/moderasi-ulasan', [UlasanController::class, 'moderationIndex'])->name('reviews.moderation');
        Route::delete('/moderasi-ulasan/{review}', [UlasanController::class, 'destroy'])->name('reviews.destroy');
    });

    // Profil User & OAuth Disconnect
    Route::get('/profile', [ProfilController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfilController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfilController::class, 'destroy'])->name('profile.destroy');
    Route::post('/auth/google/disconnect', [GoogleController::class, 'disconnectGoogle'])->name('google.disconnect');
});


/*
|--------------------------------------------------------------------------
| 3. UTILITAS & SEO
|--------------------------------------------------------------------------
*/

// Clear Cache Server
Route::get('/clear-cache-now', function () {
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    return 'Cache Route & Config Server Berhasil Dibersihkan!';
});

// Rute Darurat untuk Menjalankan Migrasi di Hosting
Route::get('/run-migration-now', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return 'Sukses! Migrasi database berhasil dijalankan di hosting.';
    } catch (\Exception $e) {
        return 'Gagal / Terjadi Error: ' . $e->getMessage();
    }
});

// Sitemap XML
Route::get('/sitemap.xml', function () {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $xml .= '<url><loc>https://siswamart.my.id/</loc><priority>1.0</priority></url>';
    $xml .= '<url><loc>https://siswamart.my.id/kategori</loc><priority>0.8</priority></url>';
    $xml .= '<url><loc>https://siswamart.my.id/cara-memesan</loc><priority>0.8</priority></url>';
    $xml .= '<url><loc>https://siswamart.my.id/tentang-kami</loc><priority>0.8</priority></url>';
    $xml .= '<url><loc>https://siswamart.my.id/login</loc><priority>0.7</priority></url>';
    $xml .= '</urlset>';

    return response($xml, 200)->header('Content-Type', 'text/xml; charset=UTF-8');
});

// Webhook Auto-Deploy GitHub
Route::post('/deploy-siswamart-secret-11', function () {
    $output = shell_exec('cd ' . base_path() . ' && git pull origin main 2>&1');
    
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');

    return response()->json([
        'status'  => 'success',
        'message' => 'Auto deploy berhasil!',
        'output'  => $output
    ]);
});

require __DIR__ . '/auth.php';

Route::middleware('guest')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});
