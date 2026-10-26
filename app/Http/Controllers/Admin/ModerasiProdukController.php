<?php

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

class ModerasiProdukController extends Controller
{
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

    public function destroy($id_produk)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        // 1. Cari produk tanpa memicu error jika kolom 'id' tidak ada di database
        $produk = Produk::where('id_produk', $id_produk)
            ->when(Schema::hasColumn('produks', 'id'), function ($query) use ($id_produk) {
                $query->orWhere('id', $id_produk);
            })
            ->firstOrFail();

        $targetId = $produk->id_produk ?? $id_produk;

        // 2. Hapus berkas gambar produk dari storage
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

        // 3. Bersihkan data relasi terkait jika ada (dikemas try-catch agar aman)
        try { Ulasan::where('id_produk', $targetId)->delete(); } catch (\Exception $e) {}
        try { Notifikasi::where('id_produk', $targetId)->delete(); } catch (\Exception $e) {}

        // 4. Hapus record produk dari database
        $produk->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus dari katalog oleh Admin!');
    }

    public function approve($id_produk)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        $produk = Produk::where('id_produk', $id_produk)->firstOrFail();
        $produk->update(['status_moderasi' => 'disetujui']);

        return redirect()->back()->with('success', "Produk '{$produk->nama_produk}' disetujui & tayang di katalog!");
    }

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
