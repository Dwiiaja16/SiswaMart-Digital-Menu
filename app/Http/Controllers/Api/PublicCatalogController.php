<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Ulasan;
use Illuminate\Http\Request;

class PublicCatalogController extends Controller
{
    private function formatFullUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:image')) {
            return $path;
        }
        return url($path);
    }

    // 1. Ambil daftar kategori beserta jumlah produk aktif
    public function categories()
    {
        $categories = Kategori::withCount(['produk' => function ($q) {
            $q->where('status_moderasi', 'disetujui')
              ->whereNull('deleted_at')
              ->whereHas('dataPenjual', function ($p) {
                  $p->where('status_toko', '!=', 'suspended')
                    ->orWhereNull('status_toko');
              });
        }])->get();

        return response()->json(['status' => 'success', 'data' => $categories]);
    }

    // 2. Ambil daftar produk (support search, filter kategori, harga, status stok, & sorting)
    public function products(Request $request)
    {
        $query = Produk::with(['kategori', 'dataPenjual'])
            ->withAvg('ulasan', 'rating')
            ->where('status_moderasi', 'disetujui')
            ->whereNull('deleted_at')
            ->whereHas('dataPenjual', function ($q) {
                $q->where('status_toko', '!=', 'suspended')
                  ->orWhereNull('status_toko');
            });

        if ($request->filled('search')) {
            $query->where('nama_produk', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('kategori_id')) {
            $query->where('id_kategori', $request->kategori_id);
        }
        if ($request->filled('status_stok')) {
            $query->where('status_stok', $request->status_stok);
        }
        if ($request->filled('min_harga')) {
            $query->where('harga', '>=', (int) $request->min_harga);
        }
        if ($request->filled('max_harga')) {
            $query->where('harga', '<=', (int) $request->max_harga);
        }

        // Sorting default: paling populer (views terbanyak)
        if ($request->sort === 'terbaru') {
            $query->latest('id_produk');
        } elseif ($request->sort === 'termurah') {
            $query->orderBy('harga', 'asc');
        } else {
            $query->orderBy('views', 'desc');
        }

        $products = $query->get()->map(function ($item) {
            return [
                'id_produk'       => $item->id_produk,
                'nama_produk'     => $item->nama_produk,
                'harga'           => (int) $item->harga,
                'status_stok'     => $item->status_stok,
                'views'           => (int) $item->views,
                'rating_avg'      => round((float) ($item->ulasan_avg_rating ?? 0), 1),
                'foto_url'        => $this->formatFullUrl($item->foto_produk_url),
                'kategori'        => $item->kategori->nama_kategori ?? '-',
                'nama_toko'       => $item->dataPenjual->nama_toko ?? '-',
                'status_toko'     => $item->dataPenjual->status_toko ?? 'buka',
            ];
        });

        return response()->json(['status' => 'success', 'data' => $products]);
    }

    // 3. Detail Produk + Auto Increment Views + Data Nomor WA Penjual
    public function show($id)
    {
        $produk = Produk::with(['kategori', 'dataPenjual', 'ulasan' => fn($q) => $q->latest('id_ulasan')])
            ->where('status_moderasi', 'disetujui')
            ->whereNull('deleted_at')
            ->findOrFail($id);

        // Tambah total view setiap kali detail produk dibuka dari Flutter
        $produk->increment('views');

        // Normalisasi nomor WA (08xxx -> 628xxx)
        $rawWa = preg_replace('/[^0-9]/', '', $produk->dataPenjual->nomor_whatsapp ?? '');
        if (str_starts_with($rawWa, '0')) {
            $rawWa = '62' . substr($rawWa, 1);
        }

        $semuaFoto = array_map(
            fn($url) => $this->formatFullUrl($url),
            $produk->semua_foto_url
        );

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id_produk'       => $produk->id_produk,
                'nama_produk'     => $produk->nama_produk,
                'deskripsi'       => $produk->deskripsi,
                'harga'           => (int) $produk->harga,
                'status_stok'     => $produk->status_stok,
                'views'           => (int) $produk->views,
                'foto_url'        => $this->formatFullUrl($produk->foto_produk_url),
                'semua_foto_url'  => $semuaFoto,
                'kategori'        => $produk->kategori->nama_kategori ?? '-',
                'penjual'         => [
                    'nama_toko'      => $produk->dataPenjual->nama_toko ?? '-',
                    'nomor_whatsapp' => $rawWa,
                    'status_toko'    => $produk->dataPenjual->status_toko ?? 'buka',
                ],
                'rating_avg'      => round((float) $produk->ulasan->avg('rating'), 1),
                'total_ulasan'    => $produk->ulasan->count(),
                'ulasan'          => $produk->ulasan,
            ]
        ]);
    }

    // 4. Kirim Ulasan Pengunjung (Guest) + Profanity Filter
    public function storeReview(Request $request, $id)
    {
        $request->validate([
            'nama_pengunjung' => 'required|string|max:50',
            'rating'          => 'required|integer|min:1|max:5',
            'komentar'        => 'required|string|max:500',
        ]);

        // Daftar kata kasar sederhana (sesuaikan dengan filter di web kamu)
        $badWords = ['anjing', 'babi', 'bangsat', 'kontol', 'memek', 'tolol', 'goblok'];
        foreach ($badWords as $word) {
            if (stripos($request->komentar, $word) !== false || stripos($request->nama_pengunjung, $word) !== false) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Ulasan mengandung kata yang tidak pantas.'
                ], 422);
            }
        }

        $ulasan = Ulasan::create([
            'id_produk'       => $id,
            'nama_pengunjung' => $request->nama_pengunjung,
            'rating'          => $request->rating,
            'komentar'        => $request->komentar,
        ]);

        return response()->json(['status' => 'success', 'data' => $ulasan], 201);
    }
}