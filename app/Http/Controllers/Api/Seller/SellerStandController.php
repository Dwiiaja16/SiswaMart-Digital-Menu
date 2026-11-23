<?php

namespace App\Http\Controllers\Api\Seller;

use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SellerStandController extends BaseSellerApiController
{
    /**
     * GET /api/seller/dashboard
     * Statistik ringkasan produk & stand
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $authError = $this->validateSellerUser($user);
        if ($authError) {
            return $authError;
        }

        $seller = $user->dataPenjual;

        $baseQuery = Produk::where('username', $user->username);
        $totalMenu         = (clone $baseQuery)->count();
        $tersedia          = (clone $baseQuery)->where('status_stok', 'tersedia')->count();
        $habis             = (clone $baseQuery)->where('status_stok', 'habis')->count();
        $menungguModerasi  = (clone $baseQuery)->where('status_moderasi', 'menunggu')->count();
        $disetujui         = (clone $baseQuery)->where('status_moderasi', 'disetujui')->count();
        $ditolak           = (clone $baseQuery)->where('status_moderasi', 'ditolak')->count();
        $totalViews        = (int) (clone $baseQuery)->sum('views');

        return $this->successResponse([
            'stand' => [
                'nama_toko'        => $seller->nama_toko ?? '-',
                'status_stand'     => $seller->status_toko ?? 'buka',
                'nomor_whatsapp'   => $seller->nomor_whatsapp ?? '',
                'foto_penjual_url' => $this->formatFullUrl($seller?->foto_penjual),
            ],
            'statistik' => [
                'total_menu'        => $totalMenu,
                'tersedia'          => $tersedia,
                'habis'             => $habis,
                'menunggu_moderasi' => $menungguModerasi,
                'disetujui'         => $disetujui,
                'ditolak'           => $ditolak,
                'total_views'       => $totalViews,
            ],
        ], 'Data dashboard stand berhasil dimuat.');
    }

    /**
     * PATCH /api/seller/stand/status
     * Buka / tutup stand
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $authError = $this->validateSellerUser($user);
        if ($authError) {
            return $authError;
        }

        $request->validate([
            'status'      => 'required_without:status_toko|in:buka,tutup',
            'status_toko' => 'required_without:status|in:buka,tutup',
        ]);

        $seller = $user->dataPenjual;
        if (!$seller) {
            return $this->errorResponse('Data profil stand belum terdaftar.', 404);
        }

        $newStatus = $request->input('status', $request->input('status_toko'));
        $seller->update(['status_toko' => $newStatus]);

        return $this->successResponse([
            'status_stand' => $newStatus,
        ], "Status stand berhasil diubah menjadi {$newStatus}.");
    }

    /**
     * POST /api/seller/stand/profile
     * Update profil stand (nama, deskripsi, no WA, foto/banner via multipart/form-data)
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $authError = $this->validateSellerUser($user);
        if ($authError) {
            return $authError;
        }

        $seller = $user->dataPenjual;
        if (!$seller) {
            return $this->errorResponse('Data profil stand tidak ditemukan.', 404);
        }

        $request->validate([
            'nama_toko'       => 'nullable|string|max:100',
            'nama_stand'      => 'nullable|string|max:100',
            'deskripsi'       => 'nullable|string|max:1000',
            'alamat'          => 'nullable|string|max:1000',
            'nomor_whatsapp'  => 'nullable|string|max:25',
            'no_wa'           => 'nullable|string|max:25',
            'foto'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'foto_penjual'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'banner'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $dataToUpdate = [];

        // Update nama toko/stand jika dikirim
        $namaToko = $request->input('nama_toko', $request->input('nama_stand'));
        if ($namaToko !== null) {
            $dataToUpdate['nama_toko'] = $namaToko;
        }

        // Update deskripsi / alamat stand
        $deskripsi = $request->input('deskripsi', $request->input('alamat'));
        if ($deskripsi !== null) {
            $dataToUpdate['alamat'] = $deskripsi;
        }

        // Update nomor whatsapp
        $noWa = $request->input('nomor_whatsapp', $request->input('no_wa'));
        if ($noWa !== null) {
            $dataToUpdate['nomor_whatsapp'] = $noWa;
        }

        // Update file foto/banner jika diunggah via multipart/form-data
        $uploadedFile = $request->file('foto') 
            ?? $request->file('foto_penjual') 
            ?? $request->file('banner');

        if ($uploadedFile) {
            // Hapus file lama jika ada
            if ($seller->foto_penjual) {
                $oldFilename = basename($seller->foto_penjual);
                if ($oldFilename && Storage::disk('public')->exists('sellers/' . $oldFilename)) {
                    Storage::disk('public')->delete('sellers/' . $oldFilename);
                }
            }

            $path = $uploadedFile->store('sellers', 'public');
            $dataToUpdate['foto_penjual'] = '/storage/' . $path;
        }

        if (!empty($dataToUpdate)) {
            $seller->update($dataToUpdate);
        }

        $seller->refresh();

        return $this->successResponse([
            'id_penjual'       => $seller->id_penjual,
            'nama_toko'        => $seller->nama_toko,
            'deskripsi'        => $seller->alamat,
            'nomor_whatsapp'   => $seller->nomor_whatsapp,
            'status_stand'     => $seller->status_toko,
            'foto_penjual_url' => $this->formatFullUrl($seller->foto_penjual),
        ], 'Profil stand berhasil diperbarui.');
    }
}
