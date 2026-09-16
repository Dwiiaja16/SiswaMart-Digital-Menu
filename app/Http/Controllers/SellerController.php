<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;

class SellerController extends Controller
{
    public function index() // Tampilkan daftar penjual di halaman Admin
    {
        $sellers = User::where('role', 'penjual')->with('shop')->latest()->get();
        
        return Inertia::render('Admin/Sellers/Index', [
            'sellers' => $sellers
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username'        => 'required|string|max:50|unique:users,username',
            'password'        => 'required|string|min:6',
            'whatsapp_number' => 'required|string|max:20',
        ]);

        // 1. Buat User Penjual
        $seller = User::create([
            'username'        => strtolower(trim($validated['username'])),
            'password'        => bcrypt($validated['password']),
            'whatsapp_number' => $validated['whatsapp_number'],
            'role'            => 'penjual',
            'login_method'    => 'password',
        ]);

        // 2. Langsung buatkan Lapak Default di database
        $seller->shop()->create([
            'name'    => 'Lapak ' . ucfirst($seller->username),
            'is_open' => true,
        ]);

        return redirect()->back()->with('success', 'Akun penjual dan lapak berhasil dibuat!');
    }

    public function update(Request $request, User $seller) // Perbarui data penjual
    {
        $validated = $request->validate([
            'username'        => 'required|string|max:50|unique:users,username,' . $seller->id,
            'whatsapp_number' => 'required|string|max:20',
            'email'           => 'nullable|email|max:255|unique:users,email,' . $seller->id,
        ]);

        $seller->update([
            'username'        => strtolower(trim($validated['username'])),
            'name'            => strtolower(trim($validated['username'])),
            'whatsapp_number' => $validated['whatsapp_number'],
            'email'           => $validated['email'] ? strtolower(trim($validated['email'])) : null,
        ]);

        return redirect()->back()->with('success', 'Data Penjual berhasil diperbarui!');
    }

    public function destroy(User $seller) // Hapus akun penjual
    {
        if ($seller->role !== 'penjual') {
            return redirect()->back()->with('error', 'Akun Admin tidak dapat dihapus.');
        }

        $seller->delete();
        return redirect()->back()->with('success', 'Akun Penjual berhasil dihapus!');
    }

    public function toggleSuspend(User $user) // Toggle status suspend akun penjual
    {
        if ($user->role !== 'penjual') {
            return redirect()->back()->with('error', 'Hanya akun penjual yang dapat di-suspend.');
        }

        $user->update([
            'is_suspended' => !$user->is_suspended,
        ]);

        if ($user->is_suspended && $user->shop) {
            $user->shop->update(['is_open' => false]);
        }

        $status = $user->is_suspended ? 'dinonaktifkan (Suspend)' : 'diaktifkan kembali';

        return redirect()->back()->with('success', "Akun penjual {$user->username} berhasil {$status}.");
    }
}