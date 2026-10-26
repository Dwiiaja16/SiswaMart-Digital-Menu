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

        // Pengecekan aman apakah model Produk menggunakan trait SoftDeletes
        $hasSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(Produk::class));

        $sellers = User::where('role', 'penjual')
            ->with(['dataPenjual', 'produk'])
            ->withCount([
                'produk as total_produk_count',
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
            ])
            ->get();

        $reviewCounts = Ulasan::query()
            ->join('produk', 'ulasan.id_produk', '=', 'produk.id_produk')
            ->selectRaw('produk.username, COUNT(ulasan.id_ulasan) as total_reviews_count')
            ->groupBy('produk.username')
            ->pluck('total_reviews_count', 'produk.username');

        $sellersData = $sellers->map(function ($seller) use ($sevenDaysAgo, $reviewCounts) {
            $penjual = $seller->dataPenjual;
            $totalProduk = (int) ($seller->total_produk_count ?? 0);
            $produkBaru = (int) ($seller->produk_baru_count ?? 0);
            $produkDihapus = (int) ($seller->produk_dihapus_count ?? 0);
            
            // Status toko dari DB ('buka', 'tutup', atau 'suspended')
            $statusToko = $penjual?->status_toko ?? 'buka';
            $isSuspended = ($statusToko === 'suspended');
            $isTutup = ($statusToko === 'tutup');

            // Logika Badge Keaktifan yang Disesuaikan
            $activityBadge = 'active';

            if ($isSuspended) {
                // 1. Prioritas utama: Akun di-suspend Admin
                $activityBadge = 'suspended';
            } elseif ($totalProduk === 0) {
                // 2. Toko baru terdaftar tapi belum punya produk
                $activityBadge = 'new';
            } elseif ($produkBaru > 0) {
                // 3. Jika ada produk baru (7 hari terakhir), status keaktifan adalah AKTIF (meski toko lagi BUKA/TUTUP)
                $activityBadge = 'active';
            } elseif ($isTutup) {
                // 4. Jika 7 hari tidak ada produk baru & posisi toko sedang TUTUP
                $activityBadge = 'closed';
            } else {
                // 5. Jika 7 hari tidak ada produk baru & posisi toko sedang BUKA (Perlu Ditinjau)
                $activityBadge = 'passive';
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
        });

        $deletedProductsWeek = $hasSoftDeletes
            ? Produk::onlyTrashed()->where('deleted_at', '>=', $sevenDaysAgo)->count()
            : 0;

        $stats = [
            'total_active'          => $sellersData->where('activity_badge', 'active')->count(),
            'total_passive'         => $sellersData->where('activity_badge', 'passive')->count(),
            'total_suspended'       => $sellersData->where('activity_badge', 'suspended')->count(),
            'total_penjual'         => $sellersData->count(),
            // HANYA produk baru 7 hari terakhir yang sudah disetujui admin
            'new_products_week'     => Produk::where('created_at', '>=', $sevenDaysAgo)->where('status_moderasi', 'disetujui')->count(),
            'deleted_products_week' => $deletedProductsWeek,
        ];

        $page = (int) request()->get('page', 1);
        $perPage = 10;
        $paginatedSellers = new \Illuminate\Pagination\LengthAwarePaginator(
            $sellersData->forPage($page, $perPage)->values(),
            $sellersData->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return Inertia::render('Admin/Laporan/Index', [
            'sellers'    => $paginatedSellers,
            'chartShops' => $sellersData->values(),
            'stats'      => $stats,
        ]);
    }

    /**
     * Toggle Suspend khusus oleh Admin ('suspended' <-> 'buka')
     */
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