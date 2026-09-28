<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Mengambil daftar notifikasi beserta unread count untuk Admin
     */
    public function index()
    {
        $notifications = Notification::with(['product.shop'])
            ->latest()
            ->take(15)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => Notification::where('is_read', false)->count(),
        ]);
    }

    /**
     * Tandai satu notifikasi telah dibaca
     */
    public function markAsRead(Notification $notification)
    {
        if (Auth::user()?->role !== 'admin') {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        // 1. Tandai notifikasi sudah dibaca
        $notification->update([
            'is_read' => true,
        ]);

        // 2. Tentukan target redirect: jika produk masih ada, arahkan ke halaman detail produk
        if ($notification->product_id) {
            return redirect()->route('products.show', $notification->product_id);
        }

        // Jika tidak ada produk terkait, kembalikan ke dashboard
        return redirect()->route('dashboard');
    }
}