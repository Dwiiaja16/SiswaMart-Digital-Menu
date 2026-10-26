<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    /**
     * Mengambil daftar notifikasi beserta unread count (API / JSON)
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        // Bersihkan otomatis notifikasi gantung yang produknya sudah dihapus
        Notifikasi::whereNotNull('id_produk')
            ->whereDoesntHave('produk')
            ->delete();

        $query = Notifikasi::with(['produk.dataPenjual'])->latest();

        // Filter notifikasi sesuai akun yang sedang login jika bukan admin
        if (!$user->isAdmin()) {
            $query->where('username', $user->username);
        }

        $notifikasi = $query->paginate(10)->withQueryString();

        $unreadCount = Notifikasi::when(!$user->isAdmin(), function ($q) use ($user) {
                $q->where('username', $user->username);
            })
            ->where('is_read', false)
            ->count();

        return response()->json([
            'notifications' => $notifikasi,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * Tandai satu notifikasi telah dibaca & redirect ke produk terkait
     */
    public function markAsRead($id_notifikasi)
    {
        $user = Auth::user();
        $notif = Notifikasi::findOrFail($id_notifikasi);

        // Tandai sudah dibaca
        $notif->markAsRead();

        // Redirect ke detail produk jika terikat dengan produk tertentu
        if ($notif->id_produk) {
            // Validasi keberadaan produk
            if (!$notif->produk) {
                $notif->delete(); // Hapus notifikasi jika produk sudah tidak ada
                return redirect()->back()->with('error', 'Produk ini telah dihapus oleh penjual.');
            }

            return redirect()->route('katalog.show', $notif->id_produk);
        }

        return redirect()->route('dashboard');
    }
}