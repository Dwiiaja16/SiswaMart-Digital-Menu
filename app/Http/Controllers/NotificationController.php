<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with(['product.shop'])
            ->latest()
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => Notification::where('is_read', false)->count(),
        ]);
    }

    public function markAsRead(Notification $notification)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $notification->markAsRead();

        return redirect()->route('products.show', $notification->product_id)
            ->with('success', 'Notifikasi ditandai sudah dibaca.');
    }
}