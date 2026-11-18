<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DataPenjual;
use App\Models\Produk;
use App\Models\Ulasan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RekapController extends Controller
{
    public function index()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $fourteenDaysAgo = Carbon::now()->subDays(14);

        // Pengecekan aman apakah model Produk menggunakan trait SoftDeletes
        $hasSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(Produk::class));

        $sellerQuery = function () use ($sevenDaysAgo, $hasSoftDeletes) {
            return User::where('role', 'penjual')
                ->with(['dataPenjual', 'produk'])
                ->withCount([
                    // HANYA menghitung produk aktif yang SUDAH DI-ACC ADMIN (Disetujui)
                    'produk as total_produk_count' => function ($q) {
                        $q->where('status_moderasi', 'disetujui');
                    },
                    // HANYA menghitung produk baru 7 hari terakhir YANG SUDAH DI-ACC ADMIN
                    'produk as produk_baru_count' => function ($q) use ($sevenDaysAgo) {
                        $q->where('created_at', '>=', $sevenDaysAgo)
                          ->where('status_moderasi', 'disetujui');
                    },
                    // Menghitung produk yang dihapus 7 hari terakhir
                    'produk as produk_dihapus_count' => function ($q) use ($sevenDaysAgo, $hasSoftDeletes) {
                        if ($hasSoftDeletes) {
                            $q->onlyTrashed()->where('deleted_at', '>=', $sevenDaysAgo);
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    },
                ]);
        };

        // Semua data lifetime dipakai untuk statistik, pencarian global, dan ekspor.
        $allSellerModels = $sellerQuery()->get();

        $reviewCounts = Ulasan::query()
            ->join('produk', 'ulasan.id_produk', '=', 'produk.id_produk')
            ->selectRaw('produk.username, COUNT(ulasan.id_ulasan) as total_reviews_count')
            ->groupBy('produk.username')
            ->pluck('total_reviews_count', 'produk.username');

        $mapSeller = function ($seller) use ($reviewCounts, $fourteenDaysAgo) {
            $penjual = $seller->dataPenjual;
            $totalProduk = (int) ($seller->total_produk_count ?? 0);
            $produkBaru = (int) ($seller->produk_baru_count ?? 0);
            $produkDihapus = (int) ($seller->produk_dihapus_count ?? 0);
            
            // Status toko dari DB ('buka', 'tutup', atau 'suspended')
            $statusToko = $penjual?->status_toko ?? 'buka';
            $isSuspended = ($statusToko === 'suspended');
            $isTutup = ($statusToko === 'tutup');

            // Cek aktivitas terakhir (dari dataPenjual atau update/unggah produk paling baru)
            $lastActiveDate = $penjual?->updated_at ?? $seller->updated_at;
            $latestProductDate = $seller->produk->max('updated_at');

            if ($latestProductDate && Carbon::parse($latestProductDate)->gt(Carbon::parse($lastActiveDate))) {
                $lastActiveDate = $latestProductDate;
            }

            // Status aktif jika ada aktivitas dalam 14 hari terakhir
            $isAktif14Hari = $lastActiveDate ? Carbon::parse($lastActiveDate)->gte($fourteenDaysAgo) : false;

            // Logika Badge Keaktifan
            if ($isSuspended) {
                $activityBadge = 'suspended';
            } elseif ($isTutup) {
                $activityBadge = 'closed';
            } elseif ($totalProduk === 0) {
                $activityBadge = 'new';
            } elseif (!$isAktif14Hari) {
                // Toko BUKA tetapi tidak ada aktivitas lebih dari 14 hari
                $activityBadge = 'passive';
            } else {
                // Toko BUKA dan aktif dalam 14 hari terakhir
                $activityBadge = 'active';
            }

            return [
                'user_id'              => $seller->user_id ?? $seller->id,
                'username'             => $seller->username,
                'nama_toko'            => $penjual?->nama_toko ?? ('TOKO ' . strtoupper($seller->username)),
                'nama_lengkap'         => $penjual?->nama_lengkap ?? $seller->username,
                'nama_kelas'           => $penjual?->nama_kelas ?? '-',
                'email'                => $penjual?->email ?? '-',
                'nomor_whatsapp'       => $penjual?->nomor_whatsapp ?? '-',
                'status'               => $isSuspended ? 'suspended' : ($isTutup ? 'tutup' : 'active'),
                'is_open'              => $statusToko === 'buka',
                'total_produk_count'   => $totalProduk,
                'produk_baru_count'    => $produkBaru,
                'produk_dihapus_count' => $produkDihapus,
                'total_reviews_count'  => (int) ($reviewCounts[$seller->username] ?? 0),
                'activity_badge'       => $activityBadge,
            ];
        };

        $allSellers = $allSellerModels->map($mapSeller)->values();

        $deletedProductsWeek = $hasSoftDeletes
            ? Produk::onlyTrashed()->where('deleted_at', '>=', $sevenDaysAgo)->count()
            : 0;

        $stats = [
            'total_active'          => $allSellers->where('activity_badge', 'active')->count(),
            'total_passive'         => $allSellers->where('activity_badge', 'passive')->count(),
            'total_suspended'       => $allSellers->where('activity_badge', 'suspended')->count(),
            'total_penjual'         => $allSellers->count(),
            'new_products_week'     => Produk::where('created_at', '>=', $sevenDaysAgo)->where('status_moderasi', 'disetujui')->count(),
            'deleted_products_week' => $deletedProductsWeek,
        ];

        // Tabel utama tetap ringan dengan pagination query-native.
        $sellers = $sellerQuery()
            ->paginate(10)
            ->withQueryString();
        $sellers->setCollection($sellers->getCollection()->map($mapSeller)->values());

        return Inertia::render('Admin/Laporan/Index', [
            'sellers'    => $sellers,
            'allSellers' => $allSellers,
            'chartShops' => $allSellers,
            'stats'      => $stats,
        ]);
    }

    /**
     * Laporan Detail Rekapitulasi Penjual (Lifetime Admin Audit)
     */
    public function show($username)
    {
        // 1. Ambil Data User Penjual beserta Data Penjual (Profil Toko)
        $seller = User::where('username', $username)
            ->where('role', 'penjual')
            ->with('dataPenjual')
            ->firstOrFail();

        // 2. Query dasar seluruh produk penjual lifetime
        $baseProductQuery = Produk::where('username', $username)
            ->with(['kategori'])
            ->withAvg('ulasan', 'rating')
            ->withCount('ulasan')
            ->latest();

        // Ambil SEMUA produk lifetime untuk statistik, distribusi rating, dan export lengkap
        $allProducts = (clone $baseProductQuery)->get();

        // 3. Kalkulasi Statistik Lifetime dari SELURUH produk penjual
        $totalProducts    = $allProducts->count();
        $approvedProducts = $allProducts->where('status_moderasi', 'disetujui')->count();
        $pendingProducts  = $allProducts->where('status_moderasi', 'pending')->count();
        $rejectedProducts = $allProducts->where('status_moderasi', 'ditolak')->count();

        $productIds   = $allProducts->pluck('id_produk');
        $totalReviews = Ulasan::whereIn('id_produk', $productIds)->count();
        $avgRating    = Ulasan::whereIn('id_produk', $productIds)->avg('rating');

        // Distribusi Bintang Ulasan (1 s/d 5)
        $ratingDistribution = [
            5 => Ulasan::whereIn('id_produk', $productIds)->where('rating', 5)->count(),
            4 => Ulasan::whereIn('id_produk', $productIds)->where('rating', 4)->count(),
            3 => Ulasan::whereIn('id_produk', $productIds)->where('rating', 3)->count(),
            2 => Ulasan::whereIn('id_produk', $productIds)->where('rating', 2)->count(),
            1 => Ulasan::whereIn('id_produk', $productIds)->where('rating', 1)->count(),
        ];

        // 4. Ambil data produk dengan Pagination (10 per halaman) untuk tabel Inertia
        $products = (clone $baseProductQuery)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Laporan/Detail', [
            'seller'             => $seller,
            'products'           => $products,
            'allProducts'        => $allProducts,
            'stats'              => [
                'total_products'     => $totalProducts,
                'approved_products'  => $approvedProducts,
                'pending_products'   => $pendingProducts,
                'rejected_products'  => $rejectedProducts,
                'compliance_rate'    => $totalProducts > 0 ? round(($approvedProducts / $totalProducts) * 100) : 0,
                'total_reviews'      => $totalReviews,
                'avg_shop_rating'    => $avgRating ? number_format($avgRating, 1) : '0.0',
            ],
            'ratingDistribution' => $ratingDistribution,
        ]);
    }

    public function toggleSuspend($username)
    {
        try {
            $user = User::where('username', $username)
                ->where('role', 'penjual')
                ->first();

            if (!$user) {
                return redirect()->back()->with('error', 'User penjual tidak ditemukan.');
            }

            DB::transaction(function () use ($username) {
                $penjual = DataPenjual::where('username', $username)->first();

                if (!$penjual) {
                    DataPenjual::create([
                        'username'       => $username,
                        'nama_lengkap'   => $username,
                        'nama_kelas'     => '-',
                        'email'          => $username . '@suspended.local',
                        'nomor_whatsapp' => '-',
                        'status_toko'    => 'suspended',
                    ]);

                    return;
                }

                $penjual->status_toko = $penjual->status_toko === 'suspended'
                    ? 'buka'
                    : 'suspended';
                $penjual->save();
            });

            return redirect()->back()->with('success', 'Status akun toko berhasil diperbarui.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'Status akun toko gagal diperbarui.');
        }
    }
}