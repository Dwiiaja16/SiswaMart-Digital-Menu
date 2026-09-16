<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect ke Google (Arahkan pengguna ke halaman pilih akun Google)
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Callback Google Handler
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // 1. Cari user berdasarkan google_id atau email
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            // 2. Jika user tidak ditemukan ATAU rolenya 'user' biasa (Pengunjung)
            if (!$user || $user->role === 'user') {
                return redirect()->route('login')->with(
                    'error', 
                    'Fitur Login hanya diperuntukkan bagi Penjual dan Admin SiswaMart. Pengunjung dapat langsung melihat katalog dan memberikan ulasan tanpa login.'
                );
            }

            // 3. Jika akun terdaftar sebagai Penjual atau Admin, izinkan masuk
            Auth::login($user);

            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            return redirect()->route('login')->with(
                'error', 
                'Akses Login dibatasi. Halaman ini khusus untuk Pengelola Lapak (Penjual) dan Admin.'
            );
        }
    }

    /**
     * Lepas Tautan Google
     */
    public function disconnectGoogle() 
    {
        $currentUser = Auth::user();

        if ($currentUser) {
            $currentUser->update([
                'google_id'    => null,
                'login_method' => 'manual',
            ]);

            return redirect()->back()->with('success', 'Akun Google berhasil diputuskan!');
        }

        return redirect()->route('login');
    }
}