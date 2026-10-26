<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\DataPenjual;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Throwable;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        try {
            $request->validate([
                'username_or_email' => 'required|string',
            ], [
                'username_or_email.required' => 'Username atau Email wajib diisi.',
            ]);

            $input = strtolower(trim($request->username_or_email));

            // 1. Cari User berdasarkan Username di tabel users
            $user = User::where('username', $input)->first();

            // 2. Jika kolom email ada di tabel users, coba cari berdasarkan email di tabel users
            if (!$user && Schema::hasColumn('users', 'email')) {
                $user = User::where('email', $input)->first();
            }

            // 3. Fallback: Cari di tabel data_penjual (berdasarkan email atau username)
            if (!$user) {
                $penjual = DataPenjual::where('email', $input)
                    ->orWhere('username', $input)
                    ->first();

                if ($penjual) {
                    $user = User::where('username', $penjual->username)->first();
                }
            }

            if (!$user) {
                return redirect()->back()->withErrors([
                    'username_or_email' => 'Akun dengan username/email tersebut tidak ditemukan.',
                ]);
            }

            // 4. Ambil email target (Cek ketersediaan kolom email di user / fallback ke data_penjual)
            $emailTarget = null;
            if (Schema::hasColumn('users', 'email') && !empty($user->email)) {
                $emailTarget = $user->email;
            } else {
                $dataPenjual = DataPenjual::where('username', $user->username)->first();
                $emailTarget = $dataPenjual?->email;
            }

            if (!$emailTarget) {
                return redirect()->back()->withErrors([
                    'username_or_email' => 'Akun "' . $user->username . '" belum memiliki alamat email yang terdaftar.',
                ]);
            }

            // 5. Generate token dan simpan ke database
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $emailTarget],
                [
                    'token'      => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // 6. Kirim email via Mailable
            Mail::to($emailTarget)->send(new ResetPasswordMail($user, $token, $emailTarget));

            return redirect()->back()->with('status', 'Link reset password berhasil dikirim ke ' . $emailTarget);

        } catch (Throwable $e) {
            return redirect()->back()->withErrors([
                'username_or_email' => 'Sistem Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function showResetForm(Request $request, $token)
    {
        return Inertia::render('Autentikasi/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email') ?? $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token'    => 'required',
                'email'    => 'required|email',
                'password' => 'required|string|min:6|confirmed',
            ], [
                'password.required'  => 'Password baru wajib diisi.',
                'password.min'       => 'Password minimal 6 karakter.',
                'password.confirmed' => 'Konfirmasi password tidak cocok.',
            ]);

            $record = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$record || !Hash::check($request->token, $record->token)) {
                return redirect()->back()->withErrors([
                    'email' => 'Token reset password tidak valid atau sudah kadaluwarsa.',
                ]);
            }

            // Cari user di tabel users berdasarkan username dari data_penjual jika users tak ada email
            $user = null;
            if (Schema::hasColumn('users', 'email')) {
                $user = User::where('email', $request->email)->first();
            }

            if (!$user) {
                $penjual = DataPenjual::where('email', $request->email)->first();
                if ($penjual) {
                    $user = User::where('username', $penjual->username)->first();
                }
            }

            if (!$user) {
                return redirect()->back()->withErrors([
                    'email' => 'User tidak ditemukan.',
                ]);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return redirect()->route('login')->with('success', 'Password berhasil diperbarui! Silakan login.');

        } catch (Throwable $e) {
            return redirect()->back()->withErrors([
                'email' => 'Gagal mereset password: ' . $e->getMessage(),
            ]);
        }
    }
}