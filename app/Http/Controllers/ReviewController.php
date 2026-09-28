<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ReviewController extends Controller
{
    /**
     * Submit Ulasan dari Pengunjung Publik / User Terdaftar
     */
    public function store(Request $request, $product)
    {
        // 1. Resolve model product secara aman
        if (!($product instanceof Product)) {
            $product = Product::find($product);
        }

        if (!$product) {
            return redirect()->back()->with('error', 'Produk tidak ditemukan.');
        }

        $currentUser = Auth::user();

        // 2. Blokir jika user yang login adalah pemilik lapak dari produk ini (Null-Safe Check)
        if ($currentUser && isset($currentUser->shop) && $currentUser->shop) {
            $isProductOwner = (int) $currentUser->shop->id === (int) $product->shop_id;
            if ($isProductOwner) {
                return redirect()->back()->with('error', 'Akses ditolak! Anda tidak dapat memberikan ulasan pada produk di lapak Anda sendiri.');
            }
        }

        // 3. Validasi input
        $validated = $request->validate([
            'reviewer_name' => 'nullable|string|max:50',
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'required|string|max:1000',
        ]);

        // 4. Penentuan nama pengulas secara aman
        $rawReviewerName = trim($request->input('reviewer_name', ''));
        if (empty($rawReviewerName)) {
            if ($currentUser) {
                $rawReviewerName = $currentUser->username ?? $currentUser->name ?? 'Pengguna SiswaMart';
            } else {
                $rawReviewerName = 'Pengunjung SiswaMart';
            }
        }

        // 5. Sensor Kata Kasar
        $badWords = [
            'anjing', 'anj', 'anjrit', 'anjir', 'anjay',
            'babi', 'monyet', 'kunyuk', 'bajingan', 'bangsat', 'brengsek',
            'kampret', 'keparat', 'biadab', 'sialan', 'tai', 'taik',
            'goblok', 'goblog', 'tolol', 'bego', 'dongo', 'idiot', 'bodoh',
            'sinting', 'edan', 'kontol', 'memek', 'peler', 'pepek',
            'pukimak', 'cukimay', 'ngentot', 'jancok', 'jancuk', 'asu', 'ndasmu',
            'bedegong', 'belegug', 'gelo', 'silit', 'heunceut',
            'fuck', 'fucking', 'shit', 'bullshit', 'bitch', 'asshole', 'dick',
            'dumbass', 'bastard',
        ];

        $filterBadWords = function ($text) use ($badWords) {
            if (empty($text)) return '';
            foreach ($badWords as $word) {
                $text = preg_replace_callback("/\b" . preg_quote($word, '/') . "\b/i", function ($matches) {
                    $w = $matches[0];
                    if (strlen($w) <= 2) return str_repeat('*', strlen($w));
                    return substr($w, 0, 1) . str_repeat('*', strlen($w) - 2) . substr($w, -1);
                }, $text);
            }
            return $text;
        };

        $filteredName = $filterBadWords($rawReviewerName);
        $filteredComment = $filterBadWords($validated['comment']);

        // 6. Susun payload simpan
        $reviewData = [
            'product_id'    => $product->id,
            'user_id'       => $currentUser?->id,
            'reviewer_name' => $filteredName,
            'rating'        => (int) $validated['rating'],
            'comment'       => $filteredComment,
        ];

        // Cek secara otomatis apakah kolom 'is_approved' ada di DB lokal/server agar tidak crash
        if (Schema::hasColumn('reviews', 'is_approved')) {
            $reviewData['is_approved'] = true;
        }

        Review::create($reviewData);

        return redirect()->back()->with('success', 'Terima kasih! Ulasan kamu berhasil ditambahkan.');
    }

    /**
     * Tampilkan daftar ulasan di Dashboard Penjual
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $shop = $user->shop;

        if (!$shop) {
            return Inertia::render('Penjual/Reviews/Index', [
                'reviews' => [],
                'error'   => 'Anda belum mendaftarkan lapak atau toko.',
            ]);
        }

        $reviews = Review::whereHas('product', function ($query) use ($shop) {
            $query->where('shop_id', $shop->id);
        })
        ->with('product')
        ->latest()
        ->get();

        return Inertia::render('Penjual/Reviews/Index', [
            'reviews' => $reviews,
        ]);
    }
}