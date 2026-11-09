<?php

/**
 * HTTP endpoints for administrator product moderation.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\DataPenjual;
use App\Models\Notifikasi;
use App\Models\Ulasan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

/**
 * Lists and moderates submitted products for administrator review.
 *
 * Only administrators may approve, reject, or remove product listings.
 */
class ModerasiProdukController extends Controller
{
    /**
     * Show products with optional name/owner prefix and category filters.
     *
     * @param Request $request Search and category filters from the moderation page.
     * @return \Inertia\Response Inertia response for the administrator product queue.
     * @precondition The caller must be an authenticated administrator.
     * @side-effects Reads product, seller, and category records without changing moderation status.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 when the caller is not an administrator.
     */
    public function index(Request $request)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        $search = $request->input('search');
        $categoryId = $request->input('kategori_id');

        $products = Produk::with(['user.dataPenjual', 'kategori'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_produk', 'like', "{$search}%")
                      ->orWhere('name', 'like', "{$search}%");
                });
            })
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('id_kategori', $categoryId);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $sellersMap = DataPenjual::all()->keyBy('username');

        $products->through(function ($product) use ($sellersMap) {
            $seller = $sellersMap->get($product->username) 
                ?? $product->user?->dataPenjual;

            $product->nama_toko = $seller?->nama_toko 
                ?? $seller?->nama_usaha 
                ?? $seller?->nama_lengkap 
                ?? '-';

            return $product;
        });

        return Inertia::render('Admin/Produk/Index', [
            'products' => $products,
            'categories' => Kategori::orderBy('nama_kategori')->get(['id_kategori', 'nama_kategori']),
            'filters'  => [
                'search'      => $search ?? '',
                'kategori_id' => $categoryId ?? '',
            ],
        ]);
    }

    /**
     * Remove a product and its stored image files from administrator moderation.
     *
     * @param int|string $id_produk Product primary key from the route.
     * @return \Illuminate\Http\RedirectResponse Redirect with a completion message.
     * @precondition The caller must be an authenticated administrator and the product must exist.
     * @side-effects Deletes stored product images, related reviews and notifications, and the product record.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 when the caller is not an administrator.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When the product does not exist.
     */
    public function destroy($id_produk)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        // Support installations whose legacy schema still exposes the alternate primary-key column.
        $produk = Produk::where('id_produk', $id_produk)
            ->when(Schema::hasColumn('produks', 'id'), function ($query) use ($id_produk) {
                $query->orWhere('id', $id_produk);
            })
            ->firstOrFail();

        $targetId = $produk->id_produk ?? $id_produk;

        if (!empty($produk->foto_produk)) {
            $photos = is_array($produk->foto_produk) 
                ? $produk->foto_produk 
                : json_decode($produk->foto_produk, true);

            if (!is_array($photos)) {
                $photos = [$produk->foto_produk];
            }

            foreach ($photos as $photo) {
                if (is_string($photo)) {
                    $filename = basename($photo);
                    if ($filename && Storage::disk('public')->exists('products/' . $filename)) {
                        Storage::disk('public')->delete('products/' . $filename);
                    }
                }
            }
        }

        // Remove dependent records explicitly because these relations may lack cascading foreign keys.
        try { Ulasan::where('id_produk', $targetId)->delete(); } catch (\Exception $e) {}
        try { Notifikasi::where('id_produk', $targetId)->delete(); } catch (\Exception $e) {}

        $produk->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus dari katalog oleh Admin!');
    }

    /**
     * Approve a product so it may appear in the public catalog.
     *
     * @param int|string $id_produk Product primary key from the route.
     * @return \Illuminate\Http\RedirectResponse Redirect with a moderation confirmation.
     * @precondition The caller must be an authenticated administrator and the product must exist.
     * @side-effects Sets status_moderasi to "disetujui".
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 when the caller is not an administrator.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When the product does not exist.
     */
    public function approve($id_produk)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        $produk = Produk::where('id_produk', $id_produk)->firstOrFail();
        $produk->update(['status_moderasi' => 'disetujui']);

        return redirect()->back()->with('success', "Produk '{$produk->nama_produk}' disetujui & tayang di katalog!");
    }

    /**
     * Reject a product so it remains unavailable in the public catalog.
     *
     * @param int|string $id_produk Product primary key from the route.
     * @return \Illuminate\Http\RedirectResponse Redirect with a moderation confirmation.
     * @precondition The caller must be an authenticated administrator and the product must exist.
     * @side-effects Sets status_moderasi to "ditolak".
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 when the caller is not an administrator.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When the product does not exist.
     */
    public function reject($id_produk)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        $produk = Produk::where('id_produk', $id_produk)->firstOrFail();
        $produk->update(['status_moderasi' => 'ditolak']);

        return redirect()->back()->with('success', "Produk '{$produk->nama_produk}' ditolak.");
    }

}
