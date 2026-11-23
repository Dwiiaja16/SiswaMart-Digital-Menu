<?php

namespace App\Http\Controllers\Api\Seller;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SellerAuthController extends BaseSellerApiController
{
    /**
     * POST /api/seller/login
     * Login penjual & penerbitan Sanctum Token
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login'    => 'required_without_all:email,username|string',
            'email'    => 'required_without_all:login,username|string',
            'username' => 'required_without_all:login,email|string',
            'password' => 'required|string',
        ]);

        $identifier = $request->input('login') 
            ?? $request->input('username') 
            ?? $request->input('email');

        $user = User::with('dataPenjual')
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Email/Username atau password salah.', 401);
        }

        // Validasi Role
        if (!$user->isPenjual()) {
            return $this->errorResponse('Akses ditolak. Akun Anda bukan akun penjual.', 403);
        }

        // Validasi Status Suspend
        $seller = $user->dataPenjual;
        if ($seller && $seller->status_toko === 'suspended') {
            return $this->errorResponse('Akun toko Anda sedang disuspensi oleh admin. Silakan hubungi admin sekolah.', 403);
        }

        // Generate token Sanctum
        $token = $user->createToken('seller-token')->plainTextToken;

        return $this->successResponse([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'user_id'  => $user->user_id,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => $user->role,
            ],
            'stand'      => $seller ? [
                'id_penjual'       => $seller->id_penjual,
                'nama_lengkap'     => $seller->nama_lengkap,
                'nama_toko'        => $seller->nama_toko,
                'nama_kelas'       => $seller->nama_kelas,
                'nisn'             => $seller->nisn,
                'nomor_whatsapp'   => $seller->nomor_whatsapp,
                'deskripsi'        => $seller->alamat,
                'status_toko'      => $seller->status_toko ?? 'buka',
                'foto_penjual_url' => $this->formatFullUrl($seller->foto_penjual),
            ] : null,
        ], 'Login penjual berhasil.');
    }

    /**
     * POST /api/seller/logout
     * Menghapus token autentikasi saat ini
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return $this->successResponse(null, 'Logout berhasil.');
    }

    /**
     * GET /api/seller/me
     * Profil autentikasi penjual yang sedang login
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $authError = $this->validateSellerUser($user);
        if ($authError) {
            return $authError;
        }

        $seller = $user->dataPenjual;

        return $this->successResponse([
            'user'  => [
                'user_id'  => $user->user_id,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => $user->role,
            ],
            'stand' => $seller ? [
                'id_penjual'       => $seller->id_penjual,
                'nama_lengkap'     => $seller->nama_lengkap,
                'nama_toko'        => $seller->nama_toko,
                'nama_kelas'       => $seller->nama_kelas,
                'nisn'             => $seller->nisn,
                'nomor_whatsapp'   => $seller->nomor_whatsapp,
                'deskripsi'        => $seller->alamat,
                'status_toko'      => $seller->status_toko ?? 'buka',
                'foto_penjual_url' => $this->formatFullUrl($seller->foto_penjual),
            ] : null,
        ], 'Data profil berhasil diambil.');
    }
}
