<?php

namespace App\Http\Controllers;

use App\Models\Ulasan;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UlasanController extends Controller
{
    /**
     * Submit Ulasan dari Pengunjung Publik
     */
    public function store(Request $request, $id_produk)
    {
        $produk = Produk::find($id_produk);

        if (!$produk) {
            return redirect()->back()->with('error', 'Produk tidak ditemukan.');
        }

        $currentUser = Auth::user();

        // Blokir jika user terdaftar adalah Admin/Penjual
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

        // Daftar Badwords
        $badWords = [
            'anjing', 'anj', 'anjrit', 'anjir', 'babi', 'bajingan', 'bangsat',
            'bego', 'bodoh', 'brengsek', 'goblok', 'idiot', 'jancok', 'kontol',
            'monyet', 'memek', 'ngentot', 'peler', 'sialan', 'tai', 'tolol', 'fuck', 'shit'
        ];

        // Fungsi Cek Apakah Teks Mengandung Badwords
        $containsBadWords = function ($text) use ($badWords) {
            if (empty($text)) return false;
            foreach ($badWords as $word) {
                if (preg_match("/\b" . preg_quote($word, '/') . "\b/i", $text)) {
                    return true;
                }
            }
            return false;
        };

        // BILA TERDETEKSI BADWORDS: TOLAK & TAMPILKAN ERROR
        if ($containsBadWords($rawName) || $containsBadWords($validated['komentar'])) {
            return redirect()->back()->withErrors([
                'badwords' => 'Ulasan Anda terdeteksi mengandung kata-kata kasar / tidak pantas dan tidak dapat dikirim!'
            ])->withInput();
        }

        // SIMPAN HANYA BILA BERSIH DARI BADWORDS
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
     * Daftar ulasan produk milik Penjual
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
     * Moderasi Ulasan untuk Admin
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
     * Hapus Ulasan Permanen
     */
    public function destroy($id_ulasan)
    {
        $ulasan = Ulasan::findOrFail($id_ulasan);
        $ulasan->delete();

        return redirect()->back()->with('success', 'Ulasan berhasil dihapus.');
    }
}