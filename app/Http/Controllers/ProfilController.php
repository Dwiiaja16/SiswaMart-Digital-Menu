<?php

namespace App\Http\Controllers;

use App\Models\DataPenjual;
use App\Models\Produk;
use App\Models\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfilController extends Controller
{
    /**
     * Tampilkan form profil pengguna
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profil/Edit', [
            'status' => session('status'),
            'user'   => $user,
            'seller' => $user->dataPenjual,
        ]);
    }

    /**
     * Perbarui informasi profil pengguna dan data penjual
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $penjual = $user->dataPenjual;

        // Auto-mapping variabel jika dikirim dari Modal Dashboard.vue
        if ($request->has('whatsapp_number') && !$request->has('nomor_whatsapp')) {
            $request->merge(['nomor_whatsapp' => $request->whatsapp_number]);
        }

        if ($request->has('shop_name') && !$request->has('nama_toko')) {
            $request->merge(['nama_toko' => $request->shop_name]);
        }

        if ($request->has('nomor_whatsapp') && !empty($request->nomor_whatsapp)) {
            $request->merge([
                'nomor_whatsapp' => preg_replace('/[^0-9]/', '', $request->nomor_whatsapp)
            ]);
        }

        $validated = $request->validate([
            'username'         => ['required', 'string', 'max:50', 'unique:users,username,' . $user->user_id . ',user_id'],
            'nama_lengkap'     => ['nullable', 'string', 'max:255'],
            'nama_toko'        => ['nullable', 'string', 'max:255'],
            'nama_kelas'       => ['nullable', 'string', 'max:50'],
            'email'            => ['required', 'email', 'max:255', 'unique:data_penjual,email,' . ($penjual?->id_penjual ?? 'NULL') . ',id_penjual'],
            'nomor_whatsapp'   => ['nullable', 'string', 'max:20'],
            'alamat'           => ['nullable', 'string'],
            'foto_penjual'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'current_password' => ['nullable', 'string'],
            'password'         => ['nullable', 'string', 'min:6'],
        ], [
            'email.required'   => 'Email penjual wajib diisi.',
            'email.email'      => 'Format email tidak valid.',
            'email.unique'     => 'Email ini sudah terdaftar di akun lain.',
        ]);

        // Verifikasi password saat ini jika mau ubah password
        if (!empty($validated['password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                return redirect()->back()->withErrors([
                    'current_password' => 'Password saat ini tidak sesuai untuk konfirmasi keamanan.'
                ]);
            }
        }

        try {
            DB::beginTransaction();

            $oldUsername = $user->username;
            $newUsername = strtolower(trim($validated['username']));

            // Update email di model User utama
            if (!empty($validated['email'])) {
                $user->email = $validated['email'];
            }

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            // CASCADING UPDATE Username ke seluruh tabel terikat di ERD
            if ($oldUsername !== $newUsername) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                $user->username = $newUsername;
                $user->save();

                DataPenjual::where('username', $oldUsername)->update(['username' => $newUsername]);
                Produk::where('username', $oldUsername)->update(['username' => $newUsername]);
                Notifikasi::where('username', $oldUsername)->update(['username' => $newUsername]);

                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } else {
                $user->save();
            }

            // Handle Foto Penjual jika diunggah
            $fotoPath = $penjual?->foto_penjual;
            if ($request->hasFile('foto_penjual')) {
                if ($fotoPath) {
                    $oldFilename = basename($fotoPath);
                    if ($oldFilename && Storage::disk('public')->exists('sellers/' . $oldFilename)) {
                        Storage::disk('public')->delete('sellers/' . $oldFilename);
                    }
                }

                $file = $request->file('foto_penjual');
                $filename = time() . '_penjual_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                $path = $file->storeAs('sellers', $filename, 'public');
                $fotoPath = '/storage/' . $path;
            }

            // Simpan Data Penjual (tanpa fallback email @siswamart.com)
            DataPenjual::updateOrCreate(
                ['username' => $newUsername],
                [
                    'nama_lengkap'   => $validated['nama_lengkap'] ?? $penjual?->nama_lengkap ?? $newUsername,
                    'nama_toko'      => $validated['nama_toko'] ?? $penjual?->nama_toko,
                    'nama_kelas'     => $validated['nama_kelas'] ?? $penjual?->nama_kelas ?? '-',
                    'email'          => $validated['email'] ?? $penjual?->email,
                    'nomor_whatsapp' => $validated['nomor_whatsapp'] ?? $penjual?->nomor_whatsapp ?? '',
                    'alamat'         => $validated['alamat'] ?? $penjual?->alamat,
                    'foto_penjual'   => $fotoPath,
                ]
            );

            DB::commit();

            return redirect()->back()->with('success', 'Profil & Data Penjual berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors([
                'error' => 'Gagal memperbarui profil: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Hapus akun pengguna
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->dataPenjual?->foto_penjual) {
            $filename = basename($user->dataPenjual->foto_penjual);
            if ($filename && Storage::disk('public')->exists('sellers/' . $filename)) {
                Storage::disk('public')->delete('sellers/' . $filename);
            }
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}