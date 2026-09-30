<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecapController extends Controller
{
    /**
     * Tampilkan Halaman Rekapitulasi & Status Toko
     */
    public function index()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        // 1. WAJIB: Hanya ambil toko yang pemiliknya (user) ber-role 'penjual'
        $shops = Shop::whereHas('user', function ($q) {
                $q->where('role', 'penjual');
            })
            ->with('user')
            ->withCount([
                'products as new_products_count' => function ($q) use ($sevenDaysAgo) {
                    $q->where('created_at', '>=', $sevenDaysAgo);
                },
            ])
            ->get()
            ->map(function ($shop) use ($sevenDaysAgo) {
                // Parsing tanggal secara aman (mencegah error jika tipe data string)
                $lastChange = $shop->last_status_change_at 
                    ? Carbon::parse($shop->last_status_change_at) 
                    : null;

                $noRecentStatusChange = !$lastChange || $lastChange->lt($sevenDaysAgo);
                $noNewProducts = ($shop->new_products_count ?? 0) === 0;

                $activityBadge = 'active';
                if ($shop->status === 'suspended' || ($shop->user && $shop->user->is_suspended)) {
                    $activityBadge = 'suspended';
                } elseif ($noRecentStatusChange && $noNewProducts) {
                    $activityBadge = 'passive';
                }

                return [
                    'id'                    => $shop->id,
                    'name'                  => $shop->name,
                    'status'                => $shop->status ?? ($shop->is_open ? 'active' : 'suspended'),
                    'is_open'               => (bool) $shop->is_open,
                    'last_status_change_at' => $lastChange
                        ? $lastChange->diffForHumans()
                        : 'Belum pernah',
                    'new_products_count'    => (int) ($shop->new_products_count ?? 0),
                    'activity_badge'        => $activityBadge,
                    'owner'                 => [
                        'id'       => $shop->user?->id,
                        'username' => $shop->user?->username ?? $shop->user?->name ?? '-',
                        'whatsapp' => $shop->user?->whatsapp_number ?? '-',
                    ],
                ];
            });

        // 2. Ringkasan Statistik (Hanya Menghitung Produk Milik Penjual)
        $stats = [
            'total_active'      => $shops->where('activity_badge', 'active')->count(),
            'total_passive'     => $shops->where('activity_badge', 'passive')->count(),
            'total_suspended'   => $shops->where('activity_badge', 'suspended')->count(),
            'new_products_week' => Product::whereHas('shop.user', fn($q) => $q->where('role', 'penjual'))
                                        ->where('created_at', '>=', $sevenDaysAgo)
                                        ->count(),
        ];

        return Inertia::render('Admin/Recap/Index', [
            'shops' => $shops->values(),
            'stats' => $stats,
        ]);
    }

    /**
     * Toggle status suspend toko oleh Admin
     */
    public function toggleSuspend(Shop $shop)
    {
        // Proteksi Ganda: Mencegah perubahan status jika toko ternyata milik akun Admin
        if ($shop->user && $shop->user->role === 'admin') {
            return redirect()->back()->with('error', 'Toko milik akun Admin tidak dapat di-suspend.');
        }

        $newStatus = ($shop->status === 'active' || empty($shop->status)) ? 'suspended' : 'active';
        $isSuspended = ($newStatus === 'suspended');

        $shop->update([
            'status'                => $newStatus,
            'last_status_change_at' => now(),
            'is_open'               => $isSuspended ? false : $shop->is_open,
        ]);

        // Sinkronisasi status suspend ke User pemilik lapak
        if ($shop->user) {
            $shop->user->update([
                'is_suspended' => $isSuspended,
            ]);
        }

        $label = $isSuspended ? 'dinonaktifkan (Suspend)' : 'diaktifkan kembali';

        return redirect()->back()->with('success', "Lapak \"{$shop->name}\" berhasil {$label}.");
    }
}