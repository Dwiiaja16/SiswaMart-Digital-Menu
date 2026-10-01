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
use Illuminate\Support\Str;

class ProductController extends Controller
{
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

    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $shop = $this->getOrCreateShop($user);
        $products = $shop->products()->with(['categories', 'images'])->latest()->get();
        $categories = Category::all();

        return Inertia::render('Penjual/Products/Index', [
            'shop'       => $shop,
            'products'   => $products,
            'categories' => $categories,
        ]);
    }

    public function show($id)
    {
        return app(CatalogController::class)->show($id);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

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

        // 1. Simpan gambar utama dengan Sanitasi Nama File & Disk Storage
        $mainImagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_main_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            // Simpan ke storage/app/public/products
            $path = $file->storeAs('products', $filename, 'public');
            $mainImagePath = $path;
            
            // $mainImagePath = '/storage/' . $path;
        }

        $product = Product::create([
            'shop_id'      => $shop->id,
            'name'         => $request->name,
            'description'  => $request->description,
            'price'        => $request->price,
            'stock_status' => $request->stock_status,
            'image'        => $mainImagePath,
        ]);

        // 2. Simpan Galeri Foto Tambahan
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . $index . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('products', $filename, 'public');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => '/storage/' . $path,
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        $product->categories()->attach($request->category_ids);

        try {
            Notification::create([
                'product_id' => $product->id,
                'type'       => 'produk_baru',
                'is_read'    => false,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal membuat notifikasi: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Produk berhasil ditambahkan!');
    }

    public function update(Request $request, Product $product)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $shop = $this->getOrCreateShop($user);

        if ((int) $product->shop_id !== (int) $shop->id) {
            abort(403, 'Akses ditolak. Anda bukan pemilik produk ini.');
        }

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

        // Update gambar utama
        if ($request->hasFile('image')) {
            // Hapus berkas lama jika ada
            if ($product->image && str_starts_with($product->image, '/storage/')) {
                $relativeStoragePath = str_replace('/storage/', '', $product->image);
                Storage::disk('public')->delete($relativeStoragePath);
            }

            $file = $request->file('image');
            $filename = time() . '_main_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('products', $filename, 'public');

            $data['image'] = '/storage/' . $path;
        }

        $product->update($data);

        // Tambah galeri foto baru
        if ($request->hasFile('images')) {
            $lastSortOrder = $product->images()->max('sort_order') ?? 0;
            foreach ($request->file('images') as $index => $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . ($lastSortOrder + $index + 1) . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('products', $filename, 'public');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => '/storage/' . $path,
                        'sort_order' => $lastSortOrder + $index + 1,
                    ]);
                }
            }
        }

        $product->categories()->sync($request->category_ids);

        return redirect()->back()->with('success', 'Produk berhasil diperbarui!');
    }

    public function destroyImage(ProductImage $image)
    {
        $user = Auth::user();
        $shop = $user ? $this->getOrCreateShop($user) : null;

        if (!$shop || (int) $image->product?->shop_id !== (int) $shop->id) {
            abort(403, 'Akses ditolak.');
        }

        if ($image->image_path && str_starts_with($image->image_path, '/storage/')) {
            $relativeStoragePath = str_replace('/storage/', '', $image->image_path);
            Storage::disk('public')->delete($relativeStoragePath);
        }

        $image->delete();

        return redirect()->back()->with('success', 'Foto produk berhasil dihapus!');
    }

    public function destroy(Product $product)
    {
        $user = Auth::user();
        $shop = $user ? $this->getOrCreateShop($user) : null;

        if (!$shop || (int) $product->shop_id !== (int) $shop->id) {
            abort(403, 'Akses ditolak.');
        }

        if ($product->image && str_starts_with($product->image, '/storage/')) {
            $relativeStoragePath = str_replace('/storage/', '', $product->image);
            Storage::disk('public')->delete($relativeStoragePath);
        }

        foreach ($product->images as $img) {
            if ($img->image_path && str_starts_with($img->image_path, '/storage/')) {
                $relativeStoragePath = str_replace('/storage/', '', $img->image_path);
                Storage::disk('public')->delete($relativeStoragePath);
            }
        }

        $product->categories()->detach();
        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus!');
    }

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

    public function adminDestroy(Product $product)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        if ($product->image && str_starts_with($product->image, '/storage/')) {
            $relativeStoragePath = str_replace('/storage/', '', $product->image);
            Storage::disk('public')->delete($relativeStoragePath);
        }

        foreach ($product->images as $img) {
            if ($img->image_path && str_starts_with($img->image_path, '/storage/')) {
                $relativeStoragePath = str_replace('/storage/', '', $img->image_path);
                Storage::disk('public')->delete($relativeStoragePath);
            }
        }

        $product->categories()->detach();
        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus dari katalog oleh Admin.');
    }
}