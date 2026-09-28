<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shop;
use App\Models\SellerProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SellerController extends Controller
{
    public function index()
    {
        $sellers = User::where('role', 'penjual')
            ->with(['shop.products', 'sellerProfile'])
            ->latest()
            ->get();
        
        return Inertia::render('Admin/Sellers/Index', [
            'sellers' => $sellers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username'        => 'required|string|max:255|unique:users,username',
            'password'        => 'required|string|min:6',
            'whatsapp_number' => 'required|string|max:20',
            'full_name'       => 'required|string|max:255',
            'class_name'      => 'required|string|max:50',
            'nisn'            => 'nullable|string|max:10',
            'photo'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'address'         => 'nullable|string',
        ]);

        $userData = [
            'username'        => trim($validated['username']),
            'password'        => Hash::make($validated['password']),
            'whatsapp_number' => $validated['whatsapp_number'],
        ];

        if (Schema::hasColumn('users', 'name')) {
            $userData['name'] = $validated['full_name'];
        }
        if (Schema::hasColumn('users', 'role')) {
            $userData['role'] = 'penjual';
        }
        if (Schema::hasColumn('users', 'is_suspended')) {
            $userData['is_suspended'] = false;
        }

        $user = User::create($userData);

        // Upload foto ke storage/app/public/sellers
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_seller_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $file->move(storage_path('app/public/sellers'), $filename);
            $photoPath = '/storage/sellers/' . $filename;
        }

        SellerProfile::create([
            'user_id'    => $user->id,
            'full_name'  => $validated['full_name'],
            'class_name' => $validated['class_name'],
            'nisn'       => $validated['nisn'] ?? null,
            'photo_path' => $photoPath,
            'address'    => $validated['address'] ?? null,
        ]);

        Shop::create([
            'user_id' => $user->id,
            'name'    => 'Lapak ' . $validated['full_name'],
            'is_open' => true,
        ]);

        return redirect()->back()->with('success', 'Akun & Profil Penjual berhasil ditambahkan!');
    }

    public function update(Request $request, User $seller)
    {
        $validated = $request->validate([
            'username'        => 'required|string|max:50|unique:users,username,' . $seller->id,
            'whatsapp_number' => 'required|string|max:20',
            'full_name'       => 'required|string|max:255',
            'class_name'      => 'required|string|max:50',
            'nisn'            => 'nullable|string|max:10',
            'photo'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'address'         => 'nullable|string',
            'password'        => 'nullable|string|min:6',
        ]);

        $cleanUsername = strtolower(trim($validated['username']));

        // 1. Update Data User Login (HANYA kolom yang benar-benar ada di tabel users)
        $updateUser = [
            'username'        => $cleanUsername,
            'whatsapp_number' => $validated['whatsapp_number'],
        ];

        // Jika tabel users memang memiliki kolom 'name', baru kita tambahkan
        if (Schema::hasColumn('users', 'name')) {
            $updateUser['name'] = $validated['full_name'];
        }

        if (!empty($validated['password'])) {
            $updateUser['password'] = Hash::make($validated['password']);
        }

        $seller->update($updateUser);

        // 2. Update atau Buat SellerProfile (Tempat Nama Lengkap & Data Profil Disimpan)
        $profile = $seller->sellerProfile ?? new SellerProfile(['user_id' => $seller->id]);

        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($profile->photo_path && !str_starts_with($profile->photo_path, 'http')) {
                $oldRelative = preg_replace('/^\/storage\//', '', $profile->photo_path);
                $oldPhysical = storage_path('app/public/' . $oldRelative);
                if (file_exists($oldPhysical)) {
                    @unlink($oldPhysical);
                }
            }

            $file = $request->file('photo');
            $filename = time() . '_seller_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $file->move(storage_path('app/public/sellers'), $filename);
            $profile->photo_path = '/storage/sellers/' . $filename;
        }

        $profile->full_name  = $validated['full_name'];
        $profile->class_name = $validated['class_name'];
        $profile->nisn       = $validated['nisn'] ?? null;
        $profile->address    = $validated['address'] ?? null;
        $profile->save();

        return redirect()->back()->with('success', 'Data Penjual berhasil diperbarui!');
    }

    public function destroy(User $seller)
    {
        if ($seller->role !== 'penjual') {
            return redirect()->back()->with('error', 'Akun Admin tidak dapat dihapus.');
        }

        if ($seller->sellerProfile && $seller->sellerProfile->photo_path && !str_starts_with($seller->sellerProfile->photo_path, 'http')) {
            $relativePath = preg_replace('/^\/storage\//', '', $seller->sellerProfile->photo_path);
            $physicalPath = storage_path('app/public/' . $relativePath);
            if (file_exists($physicalPath)) {
                @unlink($physicalPath);
            }
        }

        $seller->delete();
        return redirect()->back()->with('success', 'Akun Penjual berhasil dihapus!');
    }

    public function toggleSuspend(User $user)
    {
        if ($user->role !== 'penjual') {
            return redirect()->back()->with('error', 'Hanya akun penjual yang dapat di-suspend.');
        }

        $newSuspendedState = !$user->is_suspended;

        $user->update([
            'is_suspended' => $newSuspendedState,
        ]);

        if ($user->shop) {
            $user->shop->update([
                'status'                => $newSuspendedState ? 'suspended' : 'active',
                'is_open'               => $newSuspendedState ? false : $user->shop->is_open,
                'last_status_change_at' => now(),
            ]);
        }

        $status = $newSuspendedState ? 'dinonaktifkan (Suspend)' : 'diaktifkan kembali';

        return redirect()->back()->with('success', "Akun penjual {$user->username} berhasil {$status}.");
    }
}