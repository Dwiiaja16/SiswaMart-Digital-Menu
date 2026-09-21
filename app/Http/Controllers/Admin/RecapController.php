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
    public function index()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $shops = Shop::with('user')
            ->withCount([
                'products as new_products_count' => function ($q) use ($sevenDaysAgo) {
                    $q->where('created_at', '>=', $sevenDaysAgo);
                },
            ])
            ->get()
            ->map(function ($shop) use ($sevenDaysAgo) {
                // Tandai pasif jika: tidak ada produk baru minggu ini DAN
                // tidak pernah toggle is_open dalam 7 hari terakhir
                $lastChange = $shop->last_status_change_at;
                $noRecentStatusChange = !$lastChange || $lastChange->lt($sevenDaysAgo);
                $noNewProducts = $shop->new_products_count === 0;

                $activityBadge = 'active';
                if ($shop->status === 'suspended') {
                    $activityBadge = 'suspended';
                } elseif ($noRecentStatusChange && $noNewProducts) {
                    $activityBadge = 'passive';
                }

                return [
                    'id'                    => $shop->id,
                    'name'                  => $shop->name,
                    'status'                => $shop->status,
                    'is_open'               => (bool) $shop->is_open,
                    'last_status_change_at' => $shop->last_status_change_at
                        ? $shop->last_status_change_at->diffForHumans()
                        : 'Belum pernah',
                    'new_products_count'    => $shop->new_products_count,
                    'activity_badge'        => $activityBadge,
                    'owner'                 => [
                        'id'       => $shop->user?->id,
                        'username' => $shop->user?->username ?? '-',
                        'whatsapp' => $shop->user?->whatsapp_number ?? '-',
                    ],
                ];
            });

        // Summary stats
        $stats = [
            'total_active'    => $shops->where('activity_badge', 'active')->count(),
            'total_passive'   => $shops->where('activity_badge', 'passive')->count(),
            'total_suspended' => $shops->where('status', 'suspended')->count(),
            'new_products_week' => Product::where('created_at', '>=', $sevenDaysAgo)->count(),
        ];

        return Inertia::render('Admin/Recap/Index', [
            'shops' => $shops->values(),
            'stats' => $stats,
        ]);
    }

    public function toggleSuspend(Shop $shop)
    {
        $newStatus = $shop->status === 'active' ? 'suspended' : 'active';

        $shop->update([
            'status'                => $newStatus,
            'last_status_change_at' => now(),
            // Jika di-suspend, tutup lapak sekaligus
            'is_open'               => $newStatus === 'active' ? $shop->is_open : false,
        ]);

        $label = $newStatus === 'suspended' ? 'dinonaktifkan (Suspend)' : 'diaktifkan kembali';

        return redirect()->back()->with('success', "Lapak \"{$shop->name}\" berhasil {$label}.");
    }
}