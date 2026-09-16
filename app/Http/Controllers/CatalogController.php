<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categoryId = $request->input('category');

        $query = $this->getBaseProductQuery();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($categoryId) {
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        $products = $this->transformProducts($query->get());
        $categories = Category::all();

        return Inertia::render('Dashboard', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => [
                'search'   => $search,
                'category' => $categoryId,
            ],
        ]);
    }

    // Halaman Kategori & Filter Harga (Oatside Style)
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
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. Filter Kategori
        if ($categoryId) {
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        // 3. Filter Rentang Harga
        if ($minPrice !== null && $minPrice !== '') {
            $query->where('price', '>=', (float) $minPrice);
        }
        if ($maxPrice !== null && $maxPrice !== '') {
            $query->where('price', '<=', (float) $maxPrice);
        }

        // 4. Filter Status Stok
        if ($status) {
            $query->where('stock_status', $status);
        }

        // 5. Sorting
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } else {
            $query->latest();
        }

        $products = $this->transformProducts($query->get());

        // Sort by rating jika diminta
        if ($sort === 'rating') {
            $products = $products->sortByDesc('avg_rating')->values();
        }

        $categories = Category::withCount(['products' => function ($q) {
            $q->whereHas('shop.user', function ($qu) {
                $qu->where('is_suspended', false);
            });
        }])->get();

        $priceStats = [
            'min' => (int) (Product::whereHas('shop.user', fn($q) => $q->where('is_suspended', false))->min('price') ?? 0),
            'max' => (int) (Product::whereHas('shop.user', fn($q) => $q->where('is_suspended', false))->max('price') ?? 50000),
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

    private function getBaseProductQuery()
    {
        return Product::whereHas('shop.user', function ($q) {
            $q->where('is_suspended', false);
        })
        ->with(['shop.user', 'categories', 'reviews' => function ($q) {
            $q->where('is_approved', true)->latest();
        }]);
    }

    private function transformProducts($productCollection)
    {
        return $productCollection->map(function ($product) {
            $rawPhone = $product->shop->user->whatsapp_number ?? '';
            $phone = $product->shop->user->whatsapp_number ?? '';
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $message = "Halo {$product->shop->name}, saya mau pesan *{$product->name}* seharga Rp " . number_format($product->price, 0, ',', '.') . " melalui SiswaMart. Apakah masih ada?";
            $waUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

            $approvedReviews = $product->reviews;
            $avgRating = $approvedReviews->avg('rating') ? round($approvedReviews->avg('rating'), 1) : null;

            $imageUrl = null;
            if ($product->image) {
                if (str_starts_with($product->image, 'http')) {
                    $imageUrl = $product->image;
                } else {
                    $path = ltrim($product->image, '/');
                    if (!str_starts_with($path, 'products/')) {
                        $path = 'products/' . $path;
                    }
                    $imageUrl = asset($path);
                }
            }

            return [
                'id'           => $product->id,
                'name'         => $product->name,
                'description'  => $product->description,
                'price'        => $product->price,
                'stock_status' => $product->stock_status,
                'image'        => $imageUrl,
                'categories'   => $product->categories,

                'shop'         => [
                    'id'              => $product->shop->id,
                    'name'            => $product->shop->name,
                    'is_open'         => (bool) $product->shop->is_open,
                    'avatar_url'      => $product->shop->avatar_path ? asset('storage/' . $product->shop->avatar_path) : null,
                    'whatsapp_number' => $rawPhone,
                    'user'            => [
                        'id'              => $product->shop->user->id ?? null,
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

    // Method untuk Halaman Detail Produk
    public function show(Product $product)
    {
        // 1. Cegah akses jika penjual ter-suspend
        if ($product->shop && $product->shop->user && $product->shop->user->is_suspended) {
            abort(404, 'Produk tidak ditemukan atau toko sedang dinonaktifkan.');
        }

        // 2. Load relasi lapak, user penjual, categories (plural), dan ulasan
        $product->load([
            'shop.user', 
            'categories', 
            'reviews' => function ($q) {
                $q->where('is_approved', true)->latest();
            }
        ]);

        // 3. Format nomor WhatsApp penjual
        $rawPhone = $product->shop->user->whatsapp_number ?? '';
        $phone = $product->shop->user->whatsapp_number ?? '';
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // 4. Draf pesan WA
        $message = "Halo {$product->shop->name}, saya mau pesan *{$product->name}* seharga Rp " . number_format($product->price, 0, ',', '.') . " melalui SiswaMart. Apakah masih ada?";
        $waUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

        // 5. Ulasan & Rating
        $approvedReviews = $product->reviews;
        $avgRating = $approvedReviews->avg('rating') ? round($approvedReviews->avg('rating'), 1) : null;

        // 6. Susun Array Data Produk
        $productData = [
            'id'           => $product->id,
            'name'         => $product->name,
            'description'  => $product->description,
            'price'        => $product->price,
            'stock_status' => $product->stock_status,
            'image'        => $product->image, 
            'categories'   => $product->categories,
            
            'shop'         => [
                'id'              => $product->shop->id,
                'name'            => $product->shop->name,
                'owner_name'      => $product->shop->user->username ?? 'Penjual SiswaMart',
                'is_open'         => (bool) $product->shop->is_open,
                'avatar_url'      => $product->shop->avatar_path ? asset('storage/' . $product->shop->avatar_path) : null,
                'whatsapp_number' => $rawPhone,
                'user'            => [
                    'id'              => $product->shop->user->id ?? null,
                    'whatsapp_number' => $rawPhone,
                ],
            ],
            'wa_url'        => $waUrl,
            'avg_rating'    => $avgRating,
            'total_reviews' => $approvedReviews->count(),
            'reviews'       => $approvedReviews->map(function ($rev) {
                return [
                    'id'            => $rev->id,
                    'reviewer_name' => $rev->reviewer_name,
                    'rating'        => $rev->rating,
                    'comment'       => $rev->comment,
                    'created_at'    => $rev->created_at->diffForHumans(),
                ];
            }),
        ];

        return Inertia::render('Catalog/Show', [
            'product' => $productData,
        ]);
    }
}