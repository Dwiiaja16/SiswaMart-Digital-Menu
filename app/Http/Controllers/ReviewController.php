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

        // 2. BLOKIR JIKA USER ADALAH ADMIN ATAU PENJUAL
        if ($currentUser) {
            if ($currentUser->role === 'admin' || $currentUser->role === 'penjual') {
                return redirect()->back()->with('error', 'Akses ditolak! Admin dan Penjual tidak diizinkan memberi ulasan pada produk.');
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

        // 5. Sensor Kata Kasar & Tahan Ulasan (Badwords)
        $badWords = [
            'anjing', 'anj', 'anjrit', 'anjir', 'anjay', 'anjg', 'anjuy',
            'babi', 'bangkai', 'bajingan', 'bangsat', 'bastard', 'bitch',
            'belegug', 'bedegong', 'biadab', 'bego', 'bodoh', 'bolot',
            'brengsek', 'cabul', 'cukimay', 'cukima', 'congor',
            'dongo', 'edan', 'etdah', 'gelo', 'goblok', 'goblog', 'gembel',
            'heunceut', 'idiot', 'jancok', 'jancuk', 'jembut', 'jembot',
            'kampret', 'keparat', 'kontol', 'kntl', 'kintil', 'kunyuk', 'kurang ajar',
            'lintah', 'monyet', 'memek', 'mmk', 'maho', 'meki',
            'ngentot', 'ngewe', 'njir', 'njay', 'ndasmu', 'nyemot',
            'peler', 'pepek', 'ppk', 'pukimak', 'puki', 'puancing', 'perek',
            'sinting', 'sialan', 'silit', 'setan',
            'tai', 'taik', 'toket', 'tolol', 'tuyul', 'tempek',
            'fuck', 'fucking', 'fucked', 'fucker', 'shit', 'shitty', 
            'bullshit', 'asshole', 'dick', 'dickhead', 'dumbass', 
            'cunt', 'pussy', 'whore', 'slut', 'motherfucker', 'ass', 'crap', 'prick'
        ];

        $status = 'approved'; // Default langsung tayang jika bersih
        $containsBadword = false;

        $filterBadWords = function ($text) use ($badWords, &$status, &$containsBadword) {
            if (empty($text)) return '';
            foreach ($badWords as $word) {
                if (stripos($text, $word) !== false) {
                    $status = 'pending'; // Tahan ulasan jika ada badword
                    $containsBadword = true;
                    // Sensor kata dengan bintang
                    $text = preg_replace_callback("/\b" . preg_quote($word, '/') . "\b/i", function ($matches) {
                        $w = $matches[0];
                        if (strlen($w) <= 2) return str_repeat('*', strlen($w));
                        return substr($w, 0, 1) . str_repeat('*', strlen($w) - 2) . substr($w, -1);
                    }, $text);
                }
            }
            return $text;
        };

        $filteredName = $filterBadWords($rawReviewerName);
        $filteredComment = $filterBadWords($validated['comment']);

        // 6. Simpan ke database
        $reviewData = [
            'product_id'    => $product->id,
            'user_id'       => $currentUser?->id,
            'reviewer_name' => $filteredName,
            'rating'        => (int) $validated['rating'],
            'comment'       => $filteredComment,
            'status'        => $status, 
        ];

        if (Schema::hasColumn('reviews', 'is_approved')) {
            $reviewData['is_approved'] = ($status === 'approved');
        }

        Review::create($reviewData);

        $message = $containsBadword 
            ? 'Ulasan kamu mengandung kata sensitif dan sedang ditahan untuk ditinjau oleh Admin.' 
            : 'Terima kasih! Ulasan kamu berhasil ditambahkan.';

        return redirect()->back()->with('success', $message);
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
                'error'   => 'Anda belum mendaftarkan toko.',
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

    // === FITUR MODERASI KHUSUS ADMIN ===

    public function moderationIndex()
    {
        // Kirim ulasan pending dan semua ulasan untuk tab arsip
        $pendingReviews = Review::where('status', 'pending')
            ->with(['user', 'product.shop'])
            ->latest()
            ->get();
            
        $allReviews = Review::with(['user', 'product.shop'])
            ->latest()
            ->get();

        return Inertia::render('Admin/Reviews/Moderation', [
            'pendingReviews' => $pendingReviews,
            'allReviews'     => $allReviews
        ]);
    }

    public function approve(Review $review)
    {
        $updateData = ['status' => 'approved'];
        if (Schema::hasColumn('reviews', 'is_approved')) {
            $updateData['is_approved'] = true;
        }

        $review->update($updateData);
        return redirect()->back()->with('success', 'Ulasan berhasil diizinkan dan kini tampil ke publik.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return redirect()->back()->with('success', 'Ulasan berhasil dihapus secara permanen.');
    }
}