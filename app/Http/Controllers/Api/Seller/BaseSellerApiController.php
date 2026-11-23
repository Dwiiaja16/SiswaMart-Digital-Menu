<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BaseSellerApiController extends Controller
{
    /**
     * Format respon sukses konsisten
     */
    protected function successResponse($data = null, string $message = 'Operasi berhasil', int $code = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Format respon error konsisten
     */
    protected function errorResponse(string $message = 'Terjadi kesalahan', int $code = 400, $data = null): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Validasi autentikasi penjual, role penjual, dan status penangguhan (suspend)
     */
    protected function validateSellerUser(?User $user): ?JsonResponse
    {
        if (!$user) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        if (!$user->isPenjual()) {
            return $this->errorResponse('Akses ditolak. Akun Anda bukan akun penjual.', 403);
        }

        $seller = $user->dataPenjual;
        if ($seller && $seller->status_toko === 'suspended') {
            return $this->errorResponse('Akun toko Anda sedang disuspensi oleh admin. Silakan hubungi admin sekolah.', 403);
        }

        return null;
    }

    /**
     * Format path menjadi URL absolut yang siap dipakai aplikasi mobile
     */
    protected function formatFullUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:image')) {
            return $path;
        }

        return url('/' . ltrim($path, '/'));
    }

    /**
     * Hapus seluruh aset gambar produk yang tersimpan di disk public
     */
    protected function deleteProductImages(Produk $produk): void
    {
        $photos = $produk->semua_foto_url;
        foreach ($photos as $url) {
            if (!$url) continue;
            $filename = basename($url);
            if ($filename && Storage::disk('public')->exists('products/' . $filename)) {
                Storage::disk('public')->delete('products/' . $filename);
            }
        }
    }
}
