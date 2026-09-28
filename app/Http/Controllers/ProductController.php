<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    /**
     * Helper privat untuk memastikan penjual memiliki entri Toko/Shop
     */
    private function getOrCreateShop($user)
    {
        $shop = $user->shop;
        if (!$shop) {
            $shop = Shop::create([
                'user_id' => $user->id,
                'name'    => 'Lapak ' . ($user->username ?? $user->name ?? 'Siswa'),
                'is_open' => true,
                'status'  => 'active',
            ]);
        }
        return $shop;
    }

    /**
     * Tampilkan daftar produk milik lapak penjual yang sedang login
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $shop = $this->getOrCreateShop($user);

        // Load produk beserta relasi categories dan images
        $products = $shop->products()->with(['categories', 'images'])->latest()->get();
        $categories = Category::all();

        return Inertia::render('Penjual/Products/Index', [
            'shop'       => $shop,
            'products'   => $products,
            'categories' => $categories,
        ]);
    }

    /**
     * Tampilkan detail produk untuk pengunjung / pembeli (Fallback aman)
     */
    public function show($id)
    {
        return app(CatalogController::class)->show($id);
    }

    /**
     * Tambah produk baru oleh Penjual
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // 1. Cek status suspend di paling awal
        if ($user->is_suspended) {
            return redirect()->back()->with('error', 'Akun kamu sedang dinonaktifkan (Suspend). Tidak dapat menambah produk.');
        }

        $request->validate([
            'category_ids'   => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock_status'   => 'required|in:ready,pre_order,out_of_stock',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $shop = $this->getOrCreateShop($user);

        // 2. Simpan gambar utama (thumbnail) jika ada
        $mainImagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_main_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $file->move(public_path('products'), $filename);
            $mainImagePath = '/products/' . $filename;
        }

        $product = Product::create([
            'shop_id'      => $shop->id,
            'name'         => $request->name,
            'description'  => $request->description,
            'price'        => $request->price,
            'stock_status' => $request->stock_status,
            'image'        => $mainImagePath,
        ]);

        // 3. Simpan galeri foto tambahan ke tabel product_images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . $index . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                    $file->move(public_path('products'), $filename);

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => '/products/' . $filename,
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        // 4. Attach array ID kategori ke tabel pivot category_product
        $product->categories()->attach($request->category_ids);

        // 5. Buat notifikasi produk baru (LENGKAP & AMAN DARI ERROR DATABASE)
        try {
            $notifData = [
                'type' => 'produk_baru',
            ];

            if (Schema::hasColumn('notifications', 'product_id')) {
                $notifData['product_id'] = $product->id;
            }
            if (Schema::hasColumn('notifications', 'title')) {
                $notifData['title'] = 'Produk Baru Ditambahkan';
            }
            if (Schema::hasColumn('notifications', 'message')) {
                $notifData['message'] = "Lapak '{$shop->name}' baru saja menambahkan produk: {$product->name}";
            }
            if (Schema::hasColumn('notifications', 'is_read')) {
                $notifData['is_read'] = false;
            }

            Notification::create($notifData);
        } catch (\Exception $e) {
            // Biarkan lewat tanpa menggagalkan pembuatan produk
            \Illuminate\Support\Facades\Log::error('Gagal membuat notifikasi produk baru: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Produk berhasil ditambahkan!');
    }

    /**
     * Update produk oleh Penjual
     */
    public function update(Request $request, Product $product)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $shop = $this->getOrCreateShop($user);

        // 1. Cek otorisasi pemilik
        if ((int) $product->shop_id !== (int) $shop->id) {
            abort(403, 'Akses ditolak. Anda bukan pemilik produk ini.');
        }

        // 2. Cek status suspend
        if ($user->is_suspended) {
            return redirect()->back()->with('error', 'Akun kamu sedang dinonaktifkan (Suspend). Tidak dapat mengubah produk.');
        }

        $request->validate([
            'category_ids'   => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock_status'   => 'required|in:ready,pre_order,out_of_stock',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = [
            'name'         => $request->name,
            'description'  => $request->description,
            'price'        => $request->price,
            'stock_status' => $request->stock_status,
        ];

        // 3. Update gambar utama jika ada file baru
        if ($request->hasFile('image')) {
            if ($product->image && !str_starts_with($product->image, 'http')) {
                $oldPath = public_path(ltrim($product->image, '/'));
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $file = $request->file('image');
            $filename = time() . '_main_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $file->move(public_path('products'), $filename);

            $data['image'] = '/products/' . $filename;
        }

        $product->update($data);

        // 4. Tambahkan foto galeri baru jika diunggah
        if ($request->hasFile('images')) {
            $lastSortOrder = $product->images()->max('sort_order') ?? 0;
            foreach ($request->file('images') as $index => $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . ($lastSortOrder + $index + 1) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                    $file->move(public_path('products'), $filename);

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => '/products/' . $filename,
                        'sort_order' => $lastSortOrder + $index + 1,
                    ]);
                }
            }
        }

        // 5. Sync kategori
        $product->categories()->sync($request->category_ids);

        return redirect()->back()->with('success', 'Produk berhasil diperbarui!');
    }

    /**
     * Hapus satu foto galeri tertentu
     */
    public function destroyImage(ProductImage $image)
    {
        $user = Auth::user();
        $shop = $user ? $this->getOrCreateShop($user) : null;

        if (!$shop || (int) $image->product?->shop_id !== (int) $shop->id) {
            abort(403, 'Akses ditolak.');
        }

        // Hapus fisik berkas dari folder public secara aman
        if ($image->image_path && !str_starts_with($image->image_path, 'http')) {
            $filePath = public_path(ltrim($image->image_path, '/'));
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $image->delete();

        return redirect()->back()->with('success', 'Foto produk berhasil dihapus!');
    }

    /**
     * Hapus produk beserta seluruh foto galeri oleh Penjual
     */
    public function destroy(Product $product)
    {
        $user = Auth::user();
        $shop = $user ? $this->getOrCreateShop($user) : null;

        if (!$shop || (int) $product->shop_id !== (int) $shop->id) {
            abort(403, 'Akses ditolak.');
        }

        // Hapus gambar utama
        if ($product->image && !str_starts_with($product->image, 'http')) {
            $oldPath = public_path(ltrim($product->image, '/'));
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Hapus semua foto galeri tambahan di tabel product_images
        foreach ($product->images as $img) {
            if ($img->image_path && !str_starts_with($img->image_path, 'http')) {
                $imgPath = public_path(ltrim($img->image_path, '/'));
                if (file_exists($imgPath)) {
                    @unlink($imgPath);
                }
            }
        }

        // Detach pivot categories
        $product->categories()->detach();

        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus!');
    }

    /**
     * Toggle status lapak (buka/tutup)
     */
    public function toggleShopStatus(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $shop = $this->getOrCreateShop($user);

        $shop->update([
            'is_open'               => !$shop->is_open,
            'last_status_change_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Status buka/tutup lapak berhasil diperbarui!');
    }

    /**
     * Penjual mengedit nama & deskripsi lapak miliknya sendiri
     */
    public function updateShop(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $shop = $this->getOrCreateShop($user);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $shop->update($validated);

        return redirect()->back()->with('success', 'Informasi lapak berhasil diperbarui!');
    }

    /**
     * Admin: Tampilkan semua produk katalog
     */
    public function adminIndex(Request $request)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        $search = $request->input('search');

        $products = Product::with(['shop.user', 'categories', 'images'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('shop', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters'  => ['search' => $search ?? ''],
        ]);
    }

    /**
     * Admin: Hapus Produk Pelanggaran
     */
    public function adminDestroy(Product $product)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        // Hapus fisik gambar utama
        if ($product->image && !str_starts_with($product->image, 'http')) {
            $oldPath = public_path(ltrim($product->image, '/'));
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Hapus fisik semua gambar galeri
        foreach ($product->images as $img) {
            if ($img->image_path && !str_starts_with($img->image_path, 'http')) {
                $imgPath = public_path(ltrim($img->image_path, '/'));
                if (file_exists($imgPath)) {
                    @unlink($imgPath);
                }
            }
        }

        // Detach relasi kategori
        $product->categories()->detach();

        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus dari katalog oleh Admin.');
    }
}