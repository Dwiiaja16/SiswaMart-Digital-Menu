<?php

namespace App\Http\Controllers\Penjual;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Ulasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Hanya mengambil ID produk yang sudah disetujui admin untuk kueri ulasan
        $productIds = Produk::where('username', $user->username)
            ->where('status_moderasi', 'disetujui')
            ->pluck('id_produk');

        // Mengambil daftar produk penjual yang hanya berstatus disetujui
        $products = Produk::where('username', $user->username)
            ->where('status_moderasi', 'disetujui')
            ->with(['kategori', 'ulasan'])
            ->latest()
            ->get();

        // Mengambil Top 5 Produk yang disetujui, diurutkan berdasarkan views terbanyak
        $topViewedProducts = Produk::where('username', $user->username)
            ->where('status_moderasi', 'disetujui')
            ->with(['kategori', 'ulasan'])
            ->orderBy('views', 'desc')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'id_produk'   => $p->id_produk,
                'nama_produk' => $p->nama_produk,
                'harga'       => $p->harga,
                'status_stok' => $p->status_stok,
                'views'       => $p->views ?? 0,
                'foto_produk' => $p->foto_produk_url,
                'kategori'    => $p->kategori ? ['id_kategori' => $p->kategori->id_kategori, 'nama_kategori' => $p->kategori->nama_kategori] : null,
                'avg_rating'  => $p->ulasan->avg('rating') ? round($p->ulasan->avg('rating'), 1) : null,
            ]);

        // Menghitung total views akumulasi HANYA dari produk yang disetujui
        $totalViewsAccumulated = Produk::where('username', $user->username)
            ->where('status_moderasi', 'disetujui')
            ->sum('views');

        // Menghitung produk baru yang disetujui dalam 7 hari terakhir
        $newProductsLast7Days = Produk::where('username', $user->username)
            ->where('status_moderasi', 'disetujui')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // Ulasan 7 hari terakhir hanya dari produk yang disetujui
        $totalCommentsLast7Days = Ulasan::whereIn('id_produk', $productIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $recentReviews = Ulasan::whereIn('id_produk', $productIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->with('produk')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($rev) => [
                'id_ulasan'       => $rev->id_ulasan,
                'nama_pengunjung' => $rev->nama_pengunjung ?? 'Pengunjung',
                'rating'          => (int) $rev->rating,
                'komentar'        => $rev->komentar ?? '',
                'created_at'      => $rev->created_at?->diffForHumans() ?? 'Baru saja',
                'produk'          => $rev->produk ? [
                    'id_produk'   => $rev->produk->id_produk,
                    'nama_produk' => $rev->produk->nama_produk,
                ] : null,
            ]);

        $reportProducts = $products->map(fn ($product) => [
            'id_produk'       => $product->id_produk,
            'nama_produk'     => $product->nama_produk,
            'kategori'        => $product->kategori?->nama_kategori ?? '-',
            'harga'           => $product->harga,
            'status_stok'     => $product->status_stok,
            'views'           => $product->views ?? 0,
            'total_ulasan'    => $product->ulasan->count(),
            'rata_rata_rating'=> $product->ulasan->avg('rating') ? round($product->ulasan->avg('rating'), 1) : '-',
        ]);

        return Inertia::render('Penjual/Laporan/Index', [
            'topViewedProducts'      => $topViewedProducts,
            'newProductsLast7Days'   => $newProductsLast7Days,
            'totalCommentsLast7Days' => $totalCommentsLast7Days,
            'totalViewsAccumulated'  => $totalViewsAccumulated,
            'totalProducts'          => $products->count(),
            'recentReviews'          => $recentReviews,
            'reportProducts'         => $reportProducts,
        ]);
    }
}
