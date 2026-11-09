<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DataPenjual;
use Illuminate\Support\Facades\Auth;
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
            $googleEmail = $googleUser->getEmail();

            // 1. JIKA PENGGUNA SUDAH LOGIN (PROSES TAUTKAN AKUN GMAIL)
            if (Auth::check()) {
                /** @var \App\Models\User $currentUser */
                $currentUser = Auth::user();

                // Pastikan memperbarui kolom email pada tabel users (khususnya untuk Role Admin dan pengguna aktif)
                $currentUser->email = $googleEmail;
                $currentUser->save();

                $penjual = $currentUser->dataPenjual;

                if ($penjual) {
                    $penjual->email = $googleEmail;
                    $penjual->save();
                } elseif ($currentUser->role === 'penjual') {
                    DataPenjual::create([
                        'username'     => $currentUser->username,
                        'nama_lengkap' => $currentUser->username,
                        'email'        => $googleEmail,
                    ]);
                }

                return redirect()->route('dashboard')->with('success', "Akun Google ({$googleEmail}) berhasil ditautkan!");
            }

            // 2. JIKA PENGGUNA BELUM LOGIN (PROSES LOGIN DENGAN GOOGLE)
            // Cari user langsung berdasarkan email Google di tabel users (untuk admin atau user dengan email terverifikasi)
            $user = User::where('email', $googleEmail)->first();

            // Fallback: Cari di data_penjual jika belum tersambung langsung di tabel users
            if (!$user) {
                $penjual = DataPenjual::where('email', $googleEmail)->first();
                if ($penjual) {
                    $user = User::where('username', $penjual->username)->first();
                }
            }

            if (!$user) {
                return redirect()->route('login')->with('error', "Akun Google ({$googleEmail}) belum tertaut dengan akun SiswaMart mana pun. Silakan login manual dan tautkan di menu Profil.");
            }

            if ($user->role === 'user') {
                return redirect()->route('login')->with('error', 'Akses Login dibatasi khusus untuk Penjual dan Admin SiswaMart.');
            }

            Auth::login($user);

            return redirect()->route('dashboard')->with('success', 'Berhasil login dengan akun Google!');

        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Gagal memproses autentikasi Google: ' . $e->getMessage());
        }
    }

    /**
     * Lepas Tautan Google
     */
    public function disconnectGoogle() 
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser) {
            $currentUser->email = null;
            $currentUser->save();

            if ($currentUser->dataPenjual) {
                $penjual = $currentUser->dataPenjual;
                $penjual->email = $currentUser->username . '@siswamart.com';
                $penjual->save();
            }

            return redirect()->back()->with('success', 'Tautan akun Google berhasil dilepas!');
        }

        return redirect()->back()->with('error', 'Gagal melepas akun Google.');
    }
}