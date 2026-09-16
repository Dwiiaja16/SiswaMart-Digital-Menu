<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'status' => session('status'),
            'shop' => $request->user()->shop,
        ]);
    }

    /**
     * Update the user's profile information, shop name, and password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // 1. Bersihkan tanda strip (-) dan karakter non-angka dari input nomor WA
        if ($request->has('whatsapp_number')) {
            $request->merge([
                'whatsapp_number' => preg_replace('/[^0-9]/', '', $request->whatsapp_number)
            ]);
        }

        // 2. Validasi Input Modal
        $validated = $request->validate([
            'username'        => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
            'whatsapp_number' => ['required', 'string', 'max:20'],
            'shop_name'       => ['nullable', 'string', 'max:255'],
            'name'            => ['nullable', 'string', 'max:255'],
            'password'        => ['nullable', 'string', 'min:6'],
        ]);

        // 3. Data Update untuk Tabel Users
        $userData = [
            'username'        => strtolower(trim($validated['username'])),
            'whatsapp_number' => $validated['whatsapp_number'], // Tersimpan bersih: 085624149318
        ];

        // Ganti password HANYA JIKA diisi oleh penjual
        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        // 4. Update/Create Permanen ke Tabel Shops
        $shopName = $validated['shop_name'] ?? $validated['name'] ?? null;

        if ($user->role === 'penjual' && $shopName) {
            $user->shop()->updateOrCreate(
                ['user_id' => $user->id],
                ['name' => $shopName]
            );
        }

        // Kembalikan ke Dashboard tanpa berpindah halaman
        return redirect()->back()->with('success', 'Profil dan Lapak berhasil diperbarui!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'username'        => 'required|string|max:255|unique:users,username,' . $user->id,
            'shop_name'       => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:20',
            'password'        => 'nullable|string|min:8',
        ]);

        // JIKA USER MENGISI PASSWORD BARU, VALIDASI PASSWORD LAMA
        if ($request->filled('password')) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password saat ini tidak cocok/salah.'],
                ]);
            }

            $user->password = Hash::make($request->password);
        }

        $user->username = $request->username;
        $user->save();

        // Update data toko / lapak
        if ($user->shop) {
            $user->shop->update([
                'name'            => $request->shop_name,
                'whatsapp_number' => $request->whatsapp_number,
            ]);
        }

        return redirect()->back()->with('success', 'Profil dan Lapak berhasil diperbarui!');
    }

}