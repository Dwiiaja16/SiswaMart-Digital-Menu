<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DataPenjual;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan halaman registrasi
     */
    public function create(): Response
    {
        return Inertia::render('Autentikasi/Daftar');
    }

    /**
     * Proses pendaftaran akun pengguna/penjual baru
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username'       => ['required', 'string', 'max:50', 'unique:'.User::class],
            'nama_lengkap'   => ['required', 'string', 'max:255'],
            'nama_kelas'     => ['required', 'string', 'max:50'],
            'email'          => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.DataPenjual::class],
            'nomor_whatsapp' => ['required', 'string', 'max:20'],
            'password'       => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $cleanUsername = strtolower(trim($request->username));

        $user = User::create([
            'username' => $cleanUsername,
            'password' => Hash::make($request->password),
            'role'     => 'penjual',
        ]);

        DataPenjual::create([
            'username'       => $user->username,
            'nama_lengkap'   => trim($request->nama_lengkap),
            'nama_kelas'     => trim($request->nama_kelas),
            'email'          => trim($request->email),
            'nomor_whatsapp' => preg_replace('/[^0-9]/', '', $request->nomor_whatsapp),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}