<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CatalogController extends Controller
{
    /**
     * Halaman Utama Katalog (Dashboard Publik)
     */
    public function index(Request $request) 
    {
        $search = $request->input('search');
        $categoryId = $request->input('category');

        $query = $this->getBaseProductQuery();

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        // 1. FILTER KATEGORI: Langsung ke kolom category_id
        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        $products = $this->transformProducts($query->get());
        $categories = Category::all();

        return Inertia::render('Dashboard', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => [
                'search'   => $search ?? '',
                'category' => $categoryId ?? '',
            ],
        ]);
    }

    /**
     * Halaman Kategori & Filter Harga (Oatside Style)
     */
    public function categories(Request $request)
    {
        $search = $request->input('search');
        $categoryId = $request->input('category');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $sort = $request->input('sort', 'latest');
        $status = $request->input('status');

        $query = $this->getBaseProductQuery();

        // 1. Filter Pencarian
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. Filter Kategori: Langsung ke kolom category_id
        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        // 3. Filter Rentang Harga
        if ($minPrice !== null && $minPrice !== '') {
            $query->where('price', '>=', (float) $minPrice);
        }
        if ($maxPrice !== null && $maxPrice !== '') {
            $query->where('price', '<=', (float) $maxPrice);
        }

        // 4. Filter Status Stok
        if (!empty($status)) {
            $query->where('stock_status', $status);
        }

        // 5. Sorting
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'views_desc') {
            $query->orderBy('views_count', 'desc');
        } else {
            $query->latest();
        }

        $products = $this->transformProducts($query->get());

        // 1. Sort by rating jika diminta
        if ($sort === 'rating') {
            $products = $products->sortByDesc('avg_rating')->values();
        }

        // 2. Ambil Kategori + Hitung Jumlah Produk (Filter Suspend User & Shop secara Presisi)
        $categories = Category::withCount(['products' => function ($q) {
            $q->whereHas('shop.user', function ($qu) {
                $qu->where('is_suspended', false);
            })
            ->whereHas('shop', function ($qs) {
                $qs->whereNull('status')->orWhere('status', '!=', 'suspended');
            });
        }])->get();

        // 3. Rentang Harga Produk Publik yang Aktif
        $activeProductQuery = Product::whereHas('shop.user', fn($q) => $q->where('is_suspended', false))
            ->whereHas('shop', fn($q) => $q->whereNull('status')->orWhere('status', '!=', 'suspended'));

        $priceStats = [
            'min' => (int) ((clone $activeProductQuery)->min('price') ?? 0),
            'max' => (int) ((clone $activeProductQuery)->max('price') ?? 50000),
        ];

        return Inertia::render('Categories/Index', [
            'products'   => $products,
            'categories' => $categories,
            'priceStats' => $priceStats,
            'filters'    => [
                'search'    => $search ?? '',
                'category'  => $categoryId ?? '',
                'min_price' => $minPrice ?? '',
                'max_price' => $maxPrice ?? '',
                'sort'      => $sort,
                'status'    => $status ?? '',
            ],
        ]);
    }

    /**
     * Query Dasar Produk (Anti-Crash & Null-Safe)
     */
    private function getBaseProductQuery()
    {
        return Product::whereHas('shop.user', function ($q) {
            $q->where('is_suspended', false);
        })
        ->whereHas('shop', function ($q) {
            // Null-safe status check: hanya toko aktif / tidak disuspend
            $q->where(function ($sq) {
                $sq->whereNull('status')->orWhere('status', '!=', 'suspended');
            });
        })
        ->with(['shop.user', 'category', 'images', 'reviews' => function ($q) {
            $q->where('is_approved', true)->latest();
        }]);
    }

    /**
     * Transformasi Koleksi Produk untuk Frontend
     */
    private function transformProducts($productCollection)
    {
        return $productCollection->map(function ($product) {
            $rawPhone = $product->shop?->user?->whatsapp_number ?? '';
            $phone = preg_replace('/[^0-9]/', '', (string) $rawPhone);
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $shopName = $product->shop?->name ?? 'Toko Siswa';
            $message = "Halo {$shopName}, saya mau pesan *{$product->name}* seharga Rp " . number_format($product->price, 0, ',', '.') . " melalui SiswaMart. Apakah masih ada?";
            $waUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

            $approvedReviews = $product->reviews ?? collect();
            $avgRating = $approvedReviews->avg('rating') ? round($approvedReviews->avg('rating'), 1) : null;

            $imageUrl = $product->image;
            if (!$imageUrl && $product->relationLoaded('images') && $product->images && $product->images->isNotEmpty()) {
                $firstImg = $product->images->first();
                $imageUrl = $firstImg?->image_path;
            }

            $avatarUrl = null;
            if ($product->shop) {
                $avatarUrl = $product->shop->avatar_url;
            }

            return [
                'id'           => $product->id,
                'name'         => $product->name,
                'description'  => $product->description ?? '',
                'price'        => $product->price,
                'stock_status' => $product->stock_status,
                'views_count'  => $product->views_count ?? 0,
                'image'        => $imageUrl,
                'images'       => $product->images ?? [],
                'category'     => $product->category ?? null,

                'shop'         => [
                    'id'              => $product->shop?->id,
                    'name'            => $shopName,
                    'is_open'         => (bool) ($product->shop?->is_open ?? true),
                    'avatar_url'      => $avatarUrl,
                    'whatsapp_number' => $rawPhone,
                    'user'            => [
                        'id'              => $product->shop?->user?->id ?? null,
                        'whatsapp_number' => $rawPhone,
                    ],
                ],

                'wa_url'        => $waUrl,
                'avg_rating'    => $avgRating,
                'total_reviews' => $approvedReviews->count(),
                'reviews'       => $approvedReviews,
            ];
        });
    }

   /**
     * Halaman Detail Produk (Bypass Akses Admin & Null-Safe)
     */
    public function show($product)
    {
        // 1. Ambil model produk secara presisi
        if (!($product instanceof Product)) {
            $product = Product::with([
                'shop.user',
                'category',
                'images',
                'reviews' => fn($q) => $q->where(function ($query) {
                    $query->where('status', 'approved')->orWhere('is_approved', true);
                })->latest(),
            ])->find($product);
        } else {
            $product->loadMissing([
                'shop.user',
                'category',
                'images',
                'reviews' => fn($q) => $q->where('is_approved', true)->latest(),
            ]);
        }

        // Jika produk tidak ditemukan di database
        if (!$product) {
            return redirect()->route('catalog.index')->with('error', 'Produk tidak ditemukan.');
        }

        // 2. CEK SUSPEND: Izinkan jika yang mengakses adalah ADMIN
        $currentUser = Auth::user();
        $isAdmin = $currentUser && strtolower($currentUser->role ?? '') === 'admin';
        $isShopSuspended = $product->shop?->status === 'suspended';
        $isUserSuspended = (bool) ($product->shop?->user?->is_suspended ?? false);

        if (!$isAdmin && ($isUserSuspended || $isShopSuspended)) {
            return redirect()->route('catalog.index')->with('error', 'Toko penjual ini sedang dinonaktifkan.');
        }

        // 3. Tambah jumlah tayangan produk (Hanya jika BUKAN Admin atau Penjual)
        $isRestrictedView = $currentUser && in_array(strtolower($currentUser->role ?? ''), ['admin', 'penjual', 'seller']);
        
        if (!$isRestrictedView) {
            $product->increment('views_count');
        }

        // 4. Format nomor WhatsApp
        $rawPhone = $product->shop?->user?->whatsapp_number ?? '';
        $phone = preg_replace('/[^0-9]/', '', (string) $rawPhone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $shopName = $product->shop?->name ?? 'Toko Siswa';
        $message = "Halo {$shopName}, saya mau pesan *{$product->name}* seharga Rp " . number_format($product->price, 0, ',', '.') . " melalui SiswaMart. Apakah masih ada?";
        $waUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

        $approvedReviews = $product->reviews ?? collect();
        $avgRating = $approvedReviews->avg('rating') ? round($approvedReviews->avg('rating'), 1) : null;

        $isOwner = false;
        if ($currentUser && $currentUser->shop && $product->shop) {
            $isOwner = (int) $currentUser->shop->id === (int) $product->shop->id;
        }

        $hasReviewed = false;
        if ($currentUser && $product->reviews) {
            $hasReviewed = $product->reviews->contains('user_id', $currentUser->id);
        }

        $mainImage = $product->image ?: ($product->images?->first()?->image_path ?? null);

        // 5. Format Data Produk untuk Frontend
        $productData = [
            'id'           => $product->id,
            'name'         => $product->name,
            'description'  => $product->description ?? '',
            'price'        => $product->price,
            'stock_status' => $product->stock_status,
            'views_count'  => $product->views_count ?? 0,
            'image'        => $this->formatImageUrl($mainImage),
            'images'       => $product->images ?? [],
            'category'     => $product->category ?? null,
            'shop'         => [
                'id'              => $product->shop?->id,
                'name'            => $shopName,
                'owner_name'      => $product->shop?->user?->username ?? $product->shop?->user?->name ?? 'Penjual SiswaMart',
                'is_open'         => (bool) ($product->shop?->is_open ?? true),
                'avatar_url'      => $product->shop?->avatar_url,
                'whatsapp_number' => $rawPhone,
                'user'            => [
                    'id'              => $product->shop?->user?->id ?? null,
                    'whatsapp_number' => $rawPhone,
                ],
            ],
            'wa_url'        => $waUrl,
            'avg_rating'    => $avgRating,
            'total_reviews' => $approvedReviews->count(),
            'reviews'       => $approvedReviews->map(fn($rev) => [
                'id'            => $rev->id,
                'reviewer_name' => $rev->reviewer_name ?? 'Pengunjung',
                'rating'        => (int) $rev->rating,
                'comment'       => $rev->comment ?? '',
                'created_at'    => $rev->created_at ? $rev->created_at->diffForHumans() : 'Baru saja',
            ]),
        ];

        // 6. Produk Rekomendasi
        $categoryId = $product->category_id;
        $relatedProducts = collect();

        if ($categoryId) {
            $relatedQuery = Product::where('id', '!=', $product->id)
                ->where('category_id', $categoryId)
                ->whereHas('shop', fn($q) => $q->where('is_open', true)->where(fn($sq) => $sq->whereNull('status')->orWhere('status', '!=', 'suspended')))
                ->whereHas('shop.user', fn($q) => $q->where('is_suspended', false))
                ->with(['shop.user', 'images', 'category', 'reviews' => fn($q) => $q->where('is_approved', true)->latest()])
                ->latest()
                ->take(4);

            $relatedProducts = $this->transformProducts($relatedQuery->get());
        }

        return Inertia::render('Catalog/Show', [
            'product'         => $productData,
            'relatedProducts' => $relatedProducts,
            'isOwner'         => $isOwner,
            'hasReviewed'     => $hasReviewed,
        ]);
    }

    /**
     * Helper Privat: Format URL Gambar Produk secara Konsisten
     */
    private function formatImageUrl(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        $val = trim($imagePath);
        $lower = strtolower($val);

        if ($val === '' || $lower === 'null' || $lower === 'undefined') {
            return null;
        }

        if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, 'data:image')) {
            return $val;
        }

        $filename = basename($val);
        if (empty($filename) || strtolower($filename) === 'null' || strtolower($filename) === 'undefined') {
            return null;
        }

        return '/storage/products/' . $filename;
    }
}