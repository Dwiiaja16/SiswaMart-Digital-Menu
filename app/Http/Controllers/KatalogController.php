<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KatalogController extends Controller
{
    public function index(Request $request) 
    {
        $search = $request->input('search');
        $categoryId = $request->input('category');

        $query = $this->getBaseProductQuery();

        if (!empty($search)) {
            // Menggunakan query LIKE "search%" (Murni Awalan Kata/Huruf)
            $query->where('nama_produk', 'like', "{$search}%");
        }

        if (!empty($categoryId)) {
            $query->where('id_kategori', $categoryId);
        }

        $products = $this->transformProducts($query->get());
        
        // Hitung kategori dari produk toko yang TIDAK di-suspend
        $categories = Kategori::withCount(['produk' => function ($q) {
            $q->whereHas('dataPenjual', function ($queryPenjual) {
                $queryPenjual->where('status_toko', '!=', 'suspended');
            });
        }])->get();

        return Inertia::render('Beranda', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => [
                'search'   => $search ?? '',
                'category' => $categoryId ?? '',
            ],
        ]);
    }

    public function categories(Request $request)
    {
        $search = $request->input('search');
        $categoryId = $request->input('category');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $sort = $request->input('sort', 'popular');
        $status = $request->input('status');

        $query = $this->getBaseProductQuery();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', "{$search}%")
                ->orWhere('deskripsi', 'like', "{$search}%");
            });
        }

        if (!empty($categoryId)) {
            $query->where('id_kategori', $categoryId);
        }

        // Proteksi harga tidak boleh kurang dari 0
        if ($minPrice !== null && $minPrice !== '') {
            $query->where('harga', '>=', max(0, (float) $minPrice));
        }
        if ($maxPrice !== null && $maxPrice !== '') {
            $query->where('harga', '<=', max(0, (float) $maxPrice));
        }

        if (!empty($status)) {
            $query->where('status_stok', $status);
        }

        // Opsi Popular diurutkan murni berdasarkan VIEWS terbanyak
        if ($sort === 'popular') {
            $query->orderBy('views', 'desc');
        } elseif ($sort === 'price_asc') {
            $query->orderBy('harga', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('harga', 'desc');
        } elseif ($sort === 'reviews') {
            $query->withCount('ulasan')->orderBy('ulasan_count', 'desc');
        } else {
            $query->latest();
        }

        $products = $this->transformProducts($query->get());

        if ($sort === 'rating') {
            $products = $products->sortByDesc(fn($p) => (float) $p['avg_rating'])->values();
        }

        $categories = Kategori::withCount(['produk' => function ($q) {
            $q->whereHas('dataPenjual', function ($queryPenjual) {
                $queryPenjual->where('status_toko', '!=', 'suspended');
            });
        }])->get();

        $activeProductsQuery = Produk::whereHas('dataPenjual', function ($q) {
            $q->where('status_toko', '!=', 'suspended');
        });

        $priceStats = [
            'min' => (int) ($activeProductsQuery->min('harga') ?? 0),
            'max' => (int) ($activeProductsQuery->max('harga') ?? 50000),
        ];

        return Inertia::render('Kategori/Index', [
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
        // Hanya menampilkan produk yang disetujui & bukan dari toko yang di-SUSPEND
        return Produk::where('status_moderasi', 'disetujui')
            ->whereHas('dataPenjual', function ($q) {
                $q->where('status_toko', '!=', 'suspended');
            })
            ->with(['dataPenjual', 'kategori', 'ulasan' => function ($q) {
                $q->latest();
            }]);
    }

    private function transformProducts($productCollection)
    {
        return $productCollection->map(function ($product) {
            $penjual = $product->dataPenjual;
            $rawPhone = $penjual?->nomor_whatsapp ?? '';
            $phone = preg_replace('/[^0-9]/', '', (string) $rawPhone);
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $namaToko = $penjual?->nama_toko ?? $penjual?->nama_lengkap ?? 'Penjual SiswaMart';
            $message = "Halo {$namaToko}, saya mau pesan *{$product->nama_produk}* seharga Rp " . number_format($product->harga, 0, ',', '.') . " melalui SiswaMart. Apakah masih ada?";
            $waUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

            $ulasan = $product->ulasan ?? collect();
            $avgValue = $ulasan->avg('rating');
            $avgRating = $avgValue ? number_format($avgValue, 1, '.', '') : '0.0';

            return [
                'id_produk'     => $product->id_produk,
                'nama_produk'   => $product->nama_produk,
                'deskripsi'     => $product->deskripsi ?? '',
                'harga'         => $product->harga,
                'views'         => $product->views ?? 0,
                'status_stok'   => $product->status_stok,
                'foto_produk'   => $product->foto_produk_url,
                'kategori'      => $product->kategori ?? null,
                'penjual'       => [
                    'username'       => $product->username,
                    'nama_toko'      => $penjual?->nama_toko,
                    'nama_lengkap'   => $penjual?->nama_lengkap ?? 'Penjual SiswaMart',
                    'nama_kelas'     => $penjual?->nama_kelas ?? '',
                    'nomor_whatsapp' => $rawPhone,
                    'foto_penjual'   => $penjual?->foto_penjual ?? null,
                    'status_toko'    => $penjual?->status_toko ?? 'buka',
                ],
                'wa_url'        => $waUrl,
                'avg_rating'    => $avgRating,
                'total_reviews' => $ulasan->count(),
                'ulasan'        => $ulasan,
            ];
        });
    }

    public function show($id_produk)
    {
        $product = Produk::with(['dataPenjual', 'kategori', 'ulasan'])->find($id_produk);

        $currentUser = Auth::user();
        $isAdmin = $currentUser && $currentUser->isAdmin();

        // Blokir HANYA jika produk tidak ditemukan atau toko di-SUSPEND oleh Admin
        if (!$product || ($product->dataPenjual?->status_toko === 'suspended' && !$isAdmin)) {
            return redirect()->route('catalog.index')->with('error', 'Produk tidak ditemukan atau toko sedang ditangguhkan.');
        }

        if (!$currentUser || !in_array($currentUser->role, ['admin', 'penjual'])) {
            $product->increment('views');
        }

        $penjual = $product->dataPenjual;

        $rawPhone = $penjual?->nomor_whatsapp ?? '';
        $phone = preg_replace('/[^0-9]/', '', (string) $rawPhone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $namaToko = $penjual?->nama_toko ?? $penjual?->nama_lengkap ?? 'Penjual SiswaMart';
        $message = "Halo {$namaToko}, saya mau pesan *{$product->nama_produk}* seharga Rp " . number_format($product->harga, 0, ',', '.') . " melalui SiswaMart. Apakah masih ada?";
        $waUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

        $ulasan = $product->ulasan ?? collect();
        $avgValue = $ulasan->avg('rating');
        $avgRating = $avgValue ? number_format($avgValue, 1, '.', '') : '0.0';

        $isOwner = $currentUser && $currentUser->username === $product->username;

        $productData = [
            'id_produk'       => $product->id_produk,
            'nama_produk'     => $product->nama_produk,
            'deskripsi'       => $product->deskripsi ?? '',
            'harga'           => $product->harga,
            'views'           => $product->views ?? 0,
            'status_stok'     => $product->status_stok,
            'status_moderasi' => $product->status_moderasi ?? 'pending', // Menambahkan status moderasi
            'foto_produk'     => $product->foto_produk_url,
            'semua_foto_url'  => $product->semua_foto_url,
            'kategori'        => $product->kategori ?? null,
            'penjual'         => [
                'username'       => $product->username,
                'nama_toko'      => $penjual?->nama_toko,
                'nama_lengkap'   => $penjual?->nama_lengkap ?? 'Penjual SiswaMart',
                'nama_kelas'     => $penjual?->nama_kelas ?? '',
                'nomor_whatsapp' => $rawPhone,
                'foto_penjual'   => $penjual?->foto_penjual ?? null,
                'status_toko'    => $penjual?->status_toko ?? 'buka',
            ],
            'wa_url'          => $waUrl,
            'avg_rating'      => $avgRating,
            'total_reviews'   => $ulasan->count(),
            'ulasan'          => $ulasan->map(fn($rev) => [
                'id_ulasan'       => $rev->id_ulasan,
                'nama_pengunjung' => $rev->nama_pengunjung ?? 'Pengunjung',
                'rating'          => (int) $rev->rating,
                'komentar'        => $rev->komentar ?? '',
                'created_at'      => $rev->created_at ? $rev->created_at->diffForHumans() : 'Baru saja',
            ]),
        ];

        $relatedProducts = collect();
        if ($product->id_kategori) {
            $relatedQuery = Produk::where('id_produk', '!=', $product->id_produk)
                ->where('id_kategori', $product->id_kategori)
                ->where('status_moderasi', 'disetujui') // Produk terkait wajib yang sudah disetujui
                ->whereHas('dataPenjual', function ($q) {
                    $q->where('status_toko', '!=', 'suspended');
                })
                ->with(['dataPenjual', 'kategori', 'ulasan'])
                ->latest()
                ->take(4);

            $relatedProducts = $this->transformProducts($relatedQuery->get());
        }

        return Inertia::render('Katalog/Detail', [
            'product'         => $productData,
            'relatedProducts' => $relatedProducts,
            'isOwner'         => $isOwner,
            'isAdmin'         => $isAdmin, // Mengirim status Admin ke Vue
        ]);
    }
}