<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DataPenjual;
use App\Models\Produk;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PenjualController extends Controller
{
    public function index()
    {
        // Ambil data seluruh penjual beserta data profil dan produknya (10 per halaman)
        $sellers = User::where('role', 'penjual')
            ->with(['dataPenjual', 'produk'])
            ->latest()
            ->paginate(10)
            ->withQueryString();
        
        return Inertia::render('Admin/Penjual/Index', [
            'sellers' => $sellers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username'       => 'required|string|max:50|unique:users,username',
            'password'       => 'required|string|min:6',
            'email'          => 'required|email|max:255|unique:data_penjual,email', // Email Penjual Wajib
            'nomor_whatsapp' => 'required|string|max:20',
            'nama_lengkap'   => 'required|string|max:255',
            'nama_kelas'     => 'required|string|max:50',
            'nama_toko'      => 'nullable|string|max:255',
            'nisn'           => 'nullable|string|max:20',
            'foto_penjual'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'alamat'         => 'nullable|string',
        ], [
            'email.required' => 'Email penjual (Gmail aktif) wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.unique'   => 'Email penjual ini sudah terdaftar.',
        ]);

        try {
            DB::beginTransaction();

            $cleanUsername = strtolower(trim($validated['username']));

            $user = User::create([
                'username' => $cleanUsername,
                'email'    => $validated['email'], // Simpan email ke tabel users juga jika ada kolomnya
                'password' => Hash::make($validated['password']),
                'role'     => 'penjual',
            ]);

            $fotoPath = null;
            if ($request->hasFile('foto_penjual')) {
                $file = $request->file('foto_penjual');
                $filename = time() . '_penjual_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                $path = $file->storeAs('sellers', $filename, 'public');
                $fotoPath = '/storage/' . $path;
            }

            DataPenjual::create([
                'username'            => $user->username,
                'nama_lengkap'        => $validated['nama_lengkap'],
                'nama_kelas'          => $validated['nama_kelas'],
                'nama_toko'           => $validated['nama_toko'] ?? null,
                'nisn'                => $validated['nisn'] ?? null,
                'foto_penjual'        => $fotoPath,
                'alamat'              => $validated['alamat'] ?? null,
                'created_by_admin_id' => auth()->user()?->user_id,
                'email'               => $validated['email'],
                'nomor_whatsapp'      => preg_replace('/[^0-9]/', '', $validated['nomor_whatsapp']),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Akun & Profil Penjual berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menambahkan penjual: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $username)
    {
        $user = User::where('username', $username)->firstOrFail();
        $penjual = DataPenjual::where('username', $username)->first();

        $validated = $request->validate([
            'username'       => 'required|string|max:50|unique:users,username,' . $user->user_id . ',user_id',
            'email'          => 'required|email|max:255|unique:data_penjual,email,' . ($penjual?->id_penjual ?? 'NULL') . ',id_penjual',
            'nomor_whatsapp' => 'required|string|max:20',
            'nama_lengkap'   => 'required|string|max:255',
            'nama_kelas'     => 'required|string|max:50',
            'nama_toko'      => 'nullable|string|max:255',
            'nisn'           => 'nullable|string|max:20',
            'foto_penjual'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'alamat'         => 'nullable|string',
            'password'       => 'nullable|string|min:6',
        ]);

        try {
            DB::beginTransaction();

            $oldUsername = $user->username;
            $cleanUsername = strtolower(trim($validated['username']));

            $user->email = $validated['email'];

            if ($oldUsername !== $cleanUsername) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                $user->username = $cleanUsername;
                if (!empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                }
                $user->save();

                DataPenjual::where('username', $oldUsername)->update(['username' => $cleanUsername]);
                Produk::where('username', $oldUsername)->update(['username' => $cleanUsername]);
                Notifikasi::where('username', $oldUsername)->update(['username' => $cleanUsername]);

                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } else {
                if (!empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                }
                $user->save();
            }

            $penjual = DataPenjual::where('username', $cleanUsername)->first();

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

            if ($penjual) {
                $penjual->update([
                    'nama_lengkap'   => $validated['nama_lengkap'],
                    'nama_kelas'     => $validated['nama_kelas'],
                    'nama_toko'      => $validated['nama_toko'] ?? null,
                    'nisn'           => $validated['nisn'] ?? null,
                    'email'          => $validated['email'],
                    'nomor_whatsapp' => preg_replace('/[^0-9]/', '', $validated['nomor_whatsapp']),
                    'alamat'         => $validated['alamat'] ?? null,
                    'foto_penjual'   => $fotoPath,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Data Penjual berhasil diperbarui oleh Admin!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui penjual: ' . $e->getMessage());
        }
    }

    public function destroy($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        if ($user->role !== 'penjual') {
            return redirect()->back()->with('error', 'Akun Admin tidak dapat dihapus.');
        }

        if ($user->dataPenjual?->foto_penjual) {
            $filename = basename($user->dataPenjual->foto_penjual);
            if ($filename && Storage::disk('public')->exists('sellers/' . $filename)) {
                Storage::disk('public')->delete('sellers/' . $filename);
            }
        }

        $user->delete();
        return redirect()->back()->with('success', 'Akun Penjual berhasil dihapus!');
    }
}