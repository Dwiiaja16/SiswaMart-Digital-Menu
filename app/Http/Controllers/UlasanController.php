<?php

/**
 * HTTP endpoints for public product reviews and their seller/admin views.
 */

namespace App\Http\Controllers;

use App\Models\Ulasan;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Accepts visitor reviews and provides role-scoped review listings and moderation.
 *
 * Review submissions are limited to visitors, checked for prohibited language,
 * and stored only after validation succeeds.
 */
class UlasanController extends Controller
{
    /**
     * Store a visitor review for a product after validation and profanity screening.
     *
     * @param Request $request Review author, rating, and comment submitted by the visitor.
     * @param int|string $id_produk Product primary key from the route.
     * @return \Illuminate\Http\RedirectResponse Redirect with success, access, missing-product, validation, or profanity feedback.
     * @precondition The product must exist; ratings must be integers from 1 to 5 and comments must not exceed 1,000 characters.
     * @side-effects Creates a review only when the author is not an administrator or seller and neither the author name nor comment contains a prohibited word; an empty author name becomes "Pengunjung SiswaMart".
     * @throws \Illuminate\Validation\ValidationException When request data fails validation.
     */
    public function store(Request $request, $id_produk)
    {
        $produk = Produk::find($id_produk);

        if (!$produk) {
            return redirect()->back()->with('error', 'Produk tidak ditemukan.');
        }

        $currentUser = Auth::user();

        // Restrict review authorship to visitors so administrators and sellers cannot rate listings.
        if ($currentUser && ($currentUser->isAdmin() || $currentUser->isPenjual())) {
            return redirect()->back()->with('error', 'Akses ditolak! Admin dan Penjual tidak diizinkan memberi ulasan.');
        }

        $validated = $request->validate([
            'nama_pengunjung' => 'nullable|string|max:50',
            'rating'          => 'required|integer|min:1|max:5',
            'komentar'        => 'required|string|max:1000',
        ]);

        $rawName = trim($request->input('nama_pengunjung', ''));
        if (empty($rawName)) {
            $rawName = 'Pengunjung SiswaMart';
        }

        $badWords = [
            'anjing', 'anj', 'anjrit', 'anjir', 'babi', 'bajingan', 'bangsat',
            'bego', 'bodoh', 'brengsek', 'goblok', 'idiot', 'jancok', 'kontol',
            'monyet', 'memek', 'ngentot', 'peler', 'sialan', 'tai', 'tolol', 'fuck', 'shit'
        ];

        $containsBadWords = function ($text) use ($badWords) {
            if (empty($text)) return false;
            foreach ($badWords as $word) {
                if (preg_match("/\b" . preg_quote($word, '/') . "\b/i", $text)) {
                    return true;
                }
            }
            return false;
        };

        // Reject rather than silently mask profanity so submitted feedback is never altered without notice.
        if ($containsBadWords($rawName) || $containsBadWords($validated['komentar'])) {
            return redirect()->back()->withErrors([
                'badwords' => 'Ulasan Anda terdeteksi mengandung kata-kata kasar / tidak pantas dan tidak dapat dikirim!'
            ])->withInput();
        }

        Ulasan::create([
            'id_produk'       => $produk->id_produk,
            'nama_pengunjung' => $rawName,
            'rating'          => (int) $validated['rating'],
            'komentar'        => $validated['komentar'],
            'riwayat_ulasan'  => now(),
        ]);

        return redirect()->back()->with('success', 'Terima kasih! Ulasan kamu berhasil ditambahkan.');
    }

    /**
     * Show reviews belonging to products owned by the authenticated seller.
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse Seller review page, or redirect to login when unauthenticated.
     * @precondition The seller must be authenticated.
     * @side-effects Loads only reviews for products owned by the current seller; does not modify review records.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $reviews = Ulasan::whereHas('produk', function ($query) use ($user) {
            $query->where('username', $user->username);
        })
        ->with('produk')
        ->latest()
        ->paginate(10)
        ->withQueryString();

        return Inertia::render('Penjual/Ulasan/Index', [
            'reviews' => $reviews,
        ]);
    }

    /**
     * Show the paginated review queue used by administrator moderation.
     *
     * @return \Inertia\Response Inertia response for the administrator review moderation page.
     * @precondition The route must be protected by the administrator role middleware.
     * @side-effects Loads reviews with their product and seller data; does not modify review records.
     */
    public function moderationIndex()
    {
        $allReviews = Ulasan::with('produk.dataPenjual')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Ulasan/Moderation', [
            'allReviews' => $allReviews
        ]);
    }

    /**
     * Permanently remove a review selected by an administrator.
     *
     * @param int|string $id_ulasan Review primary key from the route.
     * @return \Illuminate\Http\RedirectResponse Redirect with a success message.
     * @precondition The review must exist and the route must be protected by the administrator role middleware.
     * @side-effects Permanently deletes the review record.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When the review does not exist.
     */
    public function destroy($id_ulasan)
    {
        $ulasan = Ulasan::findOrFail($id_ulasan);
        $ulasan->delete();

        return redirect()->back()->with('success', 'Ulasan berhasil dihapus.');
    }
}