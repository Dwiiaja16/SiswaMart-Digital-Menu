<?php

use App\Http\Controllers\Admin\RecapController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SellerController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Response;

/*
|--------------------------------------------------------------------------
| 1. ROUTE PUBLIK (Dapat Diakses Tanpa Login)
|--------------------------------------------------------------------------
*/

// Katalog & Detail Produk
Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/kategori', [CatalogController::class, 'categories'])->name('catalog.categories');
Route::get('/catalog/product/{product}', [CatalogController::class, 'show'])->name('products.show');

// Halaman Cara Memesan (Bisa diakses Pengunjung)
Route::get('/cara-memesan', function () {
    return Inertia::render('HowToOrder/Index');
})->name('how-to-order');

// Halaman Tentang Kami (Bisa diakses Pengunjung)
Route::get('/tentang-kami', function () {
    return Inertia::render('About/Index');
})->name('about');

// Ulasan Produk (Diizinkan untuk Tamu/Pengunjung Publik)
Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');

// Google OAuth (Login Utama & Callback)
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);


/*
|--------------------------------------------------------------------------
| 2. ROUTE TERPROTEKSI (Wajib Login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // === DASHBOARD UTAMA (Direct Admin vs Penjual vs Pengunjung) ===
    Route::get('/dashboard', function () {
        $user = Auth::user();

        // A. Jika Login sebagai ADMIN
        if ($user->role === 'admin') {
            return Inertia::render('Admin/Dashboard', [
                'stats' => [
                    'totalSellers' => User::where('role', 'penjual')->count(),
                    'activeShops' => Shop::where('is_open', true)->count(),
                    'totalProducts' => Product::count(),
                    'totalCategories' => Category::count(),
                ],
                'recentSellers' => User::where('role', 'penjual')
                    ->with('shop')
                    ->latest()
                    ->take(5)
                    ->get(),
            ]);
        }

        // B. Jika Login sebagai PENGUNJUNG / PEMBELI (USER) -> Tahan & kembalikan ke Beranda Utama!
        if ($user->role === 'user') {
            return redirect()->route('catalog.index');
        }

        // C. Jika Login sebagai PENJUAL (Siswa)
        if ($user->role === 'penjual') {
            $shop = $user->shop;
            if (!$shop) {
                $shop = Shop::create([
                    'user_id' => $user->id,
                    'name' => 'Lapak ' . $user->username,
                    'is_open' => true,
                ]);
            }

            $productIds = $shop->products()->pluck('id');

            return Inertia::render('Penjual/Dashboard', [
                'shop' => $shop,
                'stats' => [
                    'total_products' => $productIds->count(),
                    'ready_count' => $shop->products()->where('stock_status', 'ready')->count(),
                    'pre_order_count' => $shop->products()->where('stock_status', 'pre_order')->count(),
                    'out_of_stock_count' => $shop->products()->where('stock_status', 'out_of_stock')->count(),
                    'total_reviews' => Review::whereIn('product_id', $productIds)->count(),
                    'avg_rating' => Review::whereIn('product_id', $productIds)->avg('rating')
                        ? number_format(Review::whereIn('product_id', $productIds)->avg('rating'), 1)
                        : '0.0',
                ],
                'recentProducts' => $shop->products()->latest()->take(4)->get(),
                'recentReviews' => Review::whereIn('product_id', $productIds)
                    ->with('product')
                    ->latest()
                    ->take(4)
                    ->get(),
            ]);
        }

        return redirect()->route('catalog.index');
    })->name('dashboard');

    // === INTERAKSI PENGGUNA (Ulasan & Profil) ===
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/auth/google/disconnect', [GoogleController::class, 'disconnectGoogle'])->name('google.disconnect');

    // === FITUR KHUSUS PENJUAL ===
    Route::prefix('penjual')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    Route::post('/shop/toggle-status', [ProductController::class, 'toggleShopStatus'])->name('shop.toggle-status');
    Route::patch('/shop', [ProductController::class, 'updateShop'])->name('shop.update');
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');

    // === FITUR KHUSUS ADMIN ===
    Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

        // Management Kategori & Penjual
        Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
        Route::resource('sellers', SellerController::class)->except(['create', 'edit', 'show']);
        Route::patch('/sellers/{user}/suspend', [SellerController::class, 'toggleSuspend'])->name('sellers.toggle-suspend');

        // Moderasi Produk
        Route::get('/products', [ProductController::class, 'adminIndex'])->name('products.index');
        Route::delete('/products/{product}', [ProductController::class, 'adminDestroy'])->name('products.destroy');

        // Rekapitulasi & Suspend Lapak
        Route::get('/recap', [RecapController::class, 'index'])->name('recap');
        Route::patch('/shops/{shop}/toggle-suspend', [RecapController::class, 'toggleSuspend'])->name('shops.toggle-suspend');

        // Notifikasi Admin
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    });

});

// Pemicu Hapus Cache Server (Bisa Diakses Tanpa Login Biar Gak Keblokir Middleware)
Route::get('/clear-cache-now', function () {
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    return 'Cache Route & Config Server Berhasil Dibersihkan!';
});

// Route Sitemap untuk Google Search Console
Route::get('/sitemap.xml', function () {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $xml .= '<url><loc>https://siswamart.my.id/</loc><priority>1.0</priority></url>';
    $xml .= '<url><loc>https://siswamart.my.id/kategori</loc><priority>0.8</priority></url>';
    $xml .= '<url><loc>https://siswamart.my.id/cara-memesan</loc><priority>0.8</priority></url>';
    $xml .= '<url><loc>https://siswamart.my.id/tentang-kami</loc><priority>0.8</priority></url>';
    $xml .= '<url><loc>https://siswamart.my.id/login</loc><priority>0.7</priority></url>';
    $xml .= '</urlset>';

    return response($xml, 200)
        ->header('Content-Type', 'text/xml; charset=UTF-8');
});

require __DIR__ . '/auth.php';