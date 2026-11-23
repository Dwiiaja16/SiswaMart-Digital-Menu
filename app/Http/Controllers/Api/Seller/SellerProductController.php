<?php

namespace App\Http\Controllers\Api\Seller;

use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerProductController extends BaseSellerApiController
{
    /**
     * GET /api/seller/products
     * Daftar produk milik penjual
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $authError = $this->validateSellerUser($user);
        if ($authError) {
            return $authError;
        }

        $query = Produk::with(['kategori'])
            ->withAvg('ulasan', 'rating')
            ->withCount('ulasan')
            ->where('username', $user->username);

        // Filter pencarian
        if ($request->filled('search')) {
            $query->where('nama_produk', 'like', '%' . $request->search . '%');
        }

        // Filter status stok
        if ($request->filled('status_stok')) {
            $query->where('status_stok', $request->status_stok);
        }

        // Filter status moderasi
        if ($request->filled('status_moderasi')) {
            $query->where('status_moderasi', $request->status_moderasi);
        }

        // Filter kategori
        if ($request->filled('id_kategori')) {
            $query->where('id_kategori', $request->id_kategori);
        }

        // Sorting
        if ($request->sort === 'termurah') {
            $query->orderBy('harga', 'asc');
        } elseif ($request->sort === 'termahal') {
            $query->orderBy('harga', 'desc');
        } elseif ($request->sort === 'terpopuler') {
            $query->orderBy('views', 'desc');
        } else {
            $query->latest('id_produk');
        }

        $products = $query->get()->map(function ($item) {
            $semuaFoto = array_map(
                fn($url) => $this->formatFullUrl($url),
                $item->semua_foto_url
            );

            return [
                'id_produk'       => $item->id_produk,
                'nama_produk'     => $item->nama_produk,
                'deskripsi'       => $item->deskripsi,
                'harga'           => (int) $item->harga,
                'status_stok'     => $item->status_stok,
                'status_moderasi' => $item->status_moderasi,
                'views'           => (int) $item->views,
                'rating_avg'      => round((float) ($item->ulasan_avg_rating ?? 0), 1),
                'total_ulasan'    => (int) ($item->ulasan_count ?? 0),
                'foto_url'        => $this->formatFullUrl($item->foto_produk_url),
                'semua_foto_url'  => $semuaFoto,
                'kategori'        => [
                    'id_kategori'   => $item->kategori->id_kategori ?? null,
                    'nama_kategori' => $item->kategori->nama_kategori ?? '-',
                ],
                'created_at'      => $item->created_at?->toIso8601String(),
                'updated_at'      => $item->updated_at?->toIso8601String(),
            ];
        });

        return $this->successResponse($products, 'Daftar produk penjual berhasil diambil.');
    }

    /**
     * POST /api/seller/products
     * Tambah produk baru
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $authError = $this->validateSellerUser($user);
        if ($authError) {
            return $authError;
        }

        $request->validate([
            'nama_produk'      => 'required|string|max:100',
            'id_kategori'      => 'required|exists:kategori,id_kategori',
            'harga'            => 'required|numeric|min:0',
            'deskripsi'        => 'nullable|string',
            'status_stok'      => 'nullable|in:tersedia,habis',
            'foto_produk'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'foto_pendukung'   => 'nullable|array',
            'foto_pendukung.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $allPhotos = [];

        // Upload foto utama
        if ($request->hasFile('foto_produk')) {
            $path = $request->file('foto_produk')->store('products', 'public');
            $allPhotos[] = '/storage/' . $path;
        }

        // Upload foto pendukung opsional
        if ($request->hasFile('foto_pendukung')) {
            foreach ($request->file('foto_pendukung') as $photoFile) {
                $path = $photoFile->store('products', 'public');
                $allPhotos[] = '/storage/' . $path;
            }
        }

        $fotoDb = null;
        if (count($allPhotos) === 1) {
            $fotoDb = $allPhotos[0];
        } elseif (count($allPhotos) > 1) {
            $fotoDb = json_encode(array_values($allPhotos));
        }

        $product = Produk::create([
            'username'        => $user->username,
            'id_kategori'     => $request->id_kategori,
            'nama_produk'     => $request->nama_produk,
            'deskripsi'       => $request->deskripsi,
            'harga'           => $request->harga,
            'foto_produk'     => $fotoDb,
            'status_stok'     => $request->status_stok ?? 'tersedia',
            'status_moderasi' => 'menunggu',
            'views'           => 0,
        ]);

        $product->load('kategori');

        return $this->successResponse([
            'id_produk'       => $product->id_produk,
            'nama_produk'     => $product->nama_produk,
            'deskripsi'       => $product->deskripsi,
            'harga'           => (int) $product->harga,
            'status_stok'     => $product->status_stok,
            'status_moderasi' => $product->status_moderasi,
            'foto_url'        => $this->formatFullUrl($product->foto_produk_url),
            'semua_foto_url'  => array_map(fn($url) => $this->formatFullUrl($url), $product->semua_foto_url),
            'kategori'        => [
                'id_kategori'   => $product->kategori->id_kategori ?? null,
                'nama_kategori' => $product->kategori->nama_kategori ?? '-',
            ],
        ], 'Produk berhasil ditambahkan dan menunggu moderasi.', 201);
    }

    /**
     * POST /api/seller/products/{id} (via multipart _method=PUT) atau PUT /api/seller/products/{id}
     * Update data & foto produk dengan validasi kepemilikan dan penghapusan file lama
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $authError = $this->validateSellerUser($user);
        if ($authError) {
            return $authError;
        }

        $product = Produk::find($id);
        if (!$product) {
            return $this->errorResponse('Produk tidak ditemukan.', 404);
        }

        // Wajib validasi kepemilikan penjual
        if ($product->username !== $user->username) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk mengedit produk ini.', 403);
        }

        $request->validate([
            'nama_produk'      => 'sometimes|required|string|max:100',
            'id_kategori'      => 'sometimes|required|exists:kategori,id_kategori',
            'harga'            => 'sometimes|required|numeric|min:0',
            'deskripsi'        => 'nullable|string',
            'status_stok'      => 'nullable|in:tersedia,habis',
            'foto_produk'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'foto_pendukung'   => 'nullable|array',
            'foto_pendukung.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->has('nama_produk')) {
            $product->nama_produk = $request->nama_produk;
        }
        if ($request->has('id_kategori')) {
            $product->id_kategori = $request->id_kategori;
        }
        if ($request->has('harga')) {
            $product->harga = $request->harga;
        }
        if ($request->has('deskripsi')) {
            $product->deskripsi = $request->deskripsi;
        }
        if ($request->has('status_stok')) {
            $product->status_stok = $request->status_stok;
        }

        // Jika foto baru diunggah, hapus file gambar lama dari disk storage
        if ($request->hasFile('foto_produk')) {
            $this->deleteProductImages($product);

            $allPhotos = [];
            $path = $request->file('foto_produk')->store('products', 'public');
            $allPhotos[] = '/storage/' . $path;

            if ($request->hasFile('foto_pendukung')) {
                foreach ($request->file('foto_pendukung') as $photoFile) {
                    $path = $photoFile->store('products', 'public');
                    $allPhotos[] = '/storage/' . $path;
                }
            }

            $product->foto_produk = count($allPhotos) === 1 ? $allPhotos[0] : json_encode(array_values($allPhotos));
        }

        $product->save();
        $product->load('kategori');

        return $this->successResponse([
            'id_produk'       => $product->id_produk,
            'nama_produk'     => $product->nama_produk,
            'deskripsi'       => $product->deskripsi,
            'harga'           => (int) $product->harga,
            'status_stok'     => $product->status_stok,
            'status_moderasi' => $product->status_moderasi,
            'foto_url'        => $this->formatFullUrl($product->foto_produk_url),
            'semua_foto_url'  => array_map(fn($url) => $this->formatFullUrl($url), $product->semua_foto_url),
            'kategori'        => [
                'id_kategori'   => $product->kategori->id_kategori ?? null,
                'nama_kategori' => $product->kategori->nama_kategori ?? '-',
            ],
        ], 'Produk berhasil diperbarui.');
    }

    /**
     * PATCH /api/seller/products/{id}/stock
     * Ubah status ketersediaan stok produk (tersedia/habis)
     */
    public function updateStock(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $authError = $this->validateSellerUser($user);
        if ($authError) {
            return $authError;
        }

        $product = Produk::find($id);
        if (!$product) {
            return $this->errorResponse('Produk tidak ditemukan.', 404);
        }

        // Wajib validasi kepemilikan penjual
        if ($product->username !== $user->username) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk mengubah stok produk ini.', 403);
        }

        $request->validate([
            'status_stok' => 'required|in:tersedia,habis',
        ]);

        $product->status_stok = $request->status_stok;
        $product->save();

        return $this->successResponse([
            'id_produk'   => $product->id_produk,
            'status_stok' => $product->status_stok,
        ], "Status stok produk berhasil diubah menjadi {$product->status_stok}.");
    }

    /**
     * DELETE /api/seller/products/{id}
     * Hapus produk & hapus seluruh file gambar dari storage
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $authError = $this->validateSellerUser($user);
        if ($authError) {
            return $authError;
        }

        $product = Produk::find($id);
        if (!$product) {
            return $this->errorResponse('Produk tidak ditemukan.', 404);
        }

        // Wajib validasi kepemilikan penjual
        if ($product->username !== $user->username) {
            return $this->errorResponse('Akses ditolak. Anda tidak memiliki izin untuk menghapus produk ini.', 403);
        }

        // Wajib hapus file gambar saat delete
        $this->deleteProductImages($product);

        $product->delete();

        return $this->successResponse(null, 'Produk dan file gambar berhasil dihapus.');
    }
}
