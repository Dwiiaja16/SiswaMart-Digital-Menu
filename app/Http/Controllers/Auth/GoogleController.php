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
                $currentUser = Auth::user();
                $penjual = $currentUser->dataPenjual;

                if ($penjual) {
                    $penjual->email = $googleEmail;
                    $penjual->save();
                } else {
                    DataPenjual::create([
                        'username'     => $currentUser->username,
                        'nama_lengkap' => $currentUser->username,
                        'email'        => $googleEmail,
                    ]);
                }

                return redirect()->route('dashboard')->with('success', "Akun Google ({$googleEmail}) berhasil ditautkan!");
            }

            // 2. JIKA PENGGUNA BELUM LOGIN (PROSES LOGIN DENGAN GOOGLE)
            // Cari data_penjual berdasarkan email Google di database (Sesuai ERD)
            $penjual = DataPenjual::where('email', $googleEmail)->first();

            if (!$penjual) {
                return redirect()->route('login')->with('error', "Akun Google ({$googleEmail}) belum tertaut dengan akun penjual SiswaMart mana pun. Silakan login manual dan tautkan di menu Profil.");
            }

            $user = User::where('username', $penjual->username)->first();

            if (!$user || $user->role === 'user') {
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
        $currentUser = Auth::user();

        if ($currentUser && $currentUser->dataPenjual) {
            $penjual = $currentUser->dataPenjual;
            $penjual->email = $currentUser->username . '@siswamart.com';
            $penjual->save();

            return redirect()->back()->with('success', 'Tautan akun Google berhasil dilepas!');
        }

        return redirect()->back()->with('error', 'Gagal melepas akun Google.');
    }
}