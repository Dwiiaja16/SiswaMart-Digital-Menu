<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReviewController extends Controller
{
    // Submit Ulasan dari Katalog Publik
    public function store(Request $request, Product $product)
    {
        $currentUser = Auth::user();

        // Blokir jika yang login adalah penjual dari produk tersebut
        if ($currentUser) {
            $isProductOwner = $currentUser->shop && ($currentUser->shop->id === $product->shop_id);
            if ($isProductOwner) {
                return redirect()->back()->with('error', 'Akses ditolak! Kamu tidak dapat memberikan ulasan pada produk di lapakmu sendiri.');
            }
        }

        // 1. Validasi Input (Termasuk reviewer_name)
        $validated = $request->validate([
            'reviewer_name' => 'required|string|max:50',
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'required|string|max:1000',
        ]);

        // 2. Kamus & Fungsi Sensor Kata Kasar
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
            foreach ($badWords as $word) {
                $text = preg_replace_callback("/\b" . preg_quote($word, '/') . "\b/i", function ($matches) {
                    $w = $matches[0];
                    if (strlen($w) <= 2) return str_repeat('*', strlen($w));
                    return substr($w, 0, 1) . str_repeat('*', strlen($w) - 2) . substr($w, -1);
                }, $text);
            }
            return $text;
        };

        // 3. Sensor Nama & Komentar Inputan Pengunjung
        $filteredName = $filterBadWords($validated['reviewer_name']);
        $filteredComment = $filterBadWords($validated['comment']);

        // 4. Simpan ke Database
        Review::create([
            'product_id'    => $product->id,
            'user_id'       => $currentUser ? $currentUser->id : null,
            'reviewer_name' => $filteredName,
            'rating'        => $validated['rating'],
            'comment'       => $filteredComment,
            'is_approved'   => true, 
        ]);

        return redirect()->back()->with('success', 'Terima kasih! Ulasan kamu berhasil ditambahkan.');
    }

    // Tampilkan Ulasan di Dashboard Penjual
    public function index()
    {
        $user = Auth::user();
        $shop = $user ? $user->shop : null;

        if (!$shop) {
            return Inertia::render('Penjual/Reviews/Index', [
                'reviews' => [],
                'error'   => 'Anda belum mendaftarkan lapak atau toko.'
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