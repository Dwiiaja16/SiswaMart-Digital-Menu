<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Halaman Laporan Performa Toko Penjual
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Jika penjual belum punya toko, kembalikan data kosong
        if (!$shop) {
            return Inertia::render('Penjual/Reports/Index', [
                'topViewedProducts'     => [],
                'newProductsLast7Days'  => 0,
                'totalCommentsLast7Days'=> 0,
                'totalViewsAccumulated' => 0,
                'recentReviews'         => [],
            ]);
        }

        // Ambil semua ID produk milik toko ini
        $productIds = $shop->products()->pluck('id');

        // a. Top 5 Produk Paling Banyak Dilihat
        $topViewedProducts = $shop->products()
            ->with(['category', 'images'])
            ->orderBy('views_count', 'desc')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'views_count' => $p->views_count ?? 0,
                'price'       => $p->price,
                'stock_status'=> $p->stock_status,
                'image'       => $p->image,
                'category'    => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name] : null,
            ]);

        // b. Produk baru dalam 7 hari terakhir
        $newProductsLast7Days = $shop->products()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // c. Total ulasan dalam 7 hari terakhir
        $totalCommentsLast7Days = Review::whereIn('product_id', $productIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // d. Total tayangan akumulasi semua produk
        $totalViewsAccumulated = $shop->products()->sum('views_count');

        // e. 5 Ulasan terbaru dalam 7 hari terakhir
        $recentReviews = Review::whereIn('product_id', $productIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->with('product')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($rev) => [
                'id'            => $rev->id,
                'reviewer_name' => $rev->reviewer_name ?? 'Pengunjung',
                'rating'        => (int) $rev->rating,
                'comment'       => $rev->comment ?? '',
                'created_at'    => $rev->created_at?->diffForHumans() ?? 'Baru saja',
                'product'       => $rev->product ? [
                    'id'   => $rev->product->id,
                    'name' => $rev->product->name,
                ] : null,
            ]);

        return Inertia::render('Penjual/Reports/Index', [
            'topViewedProducts'      => $topViewedProducts,
            'newProductsLast7Days'   => $newProductsLast7Days,
            'totalCommentsLast7Days' => $totalCommentsLast7Days,
            'totalViewsAccumulated'  => (int) $totalViewsAccumulated,
            'recentReviews'          => $recentReviews,
        ]);
    }
}
