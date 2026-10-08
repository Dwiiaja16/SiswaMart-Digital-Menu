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
                'name'    => 'Toko ' . ($user->username ?? $user->name ?? 'Siswa'),
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
        $products = $shop->products()->with(['category', 'images'])->latest()->get();
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
            'category_id'  => 'required|exists:categories,id',
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'stock_status' => 'required|in:ready,pre_order,out_of_stock',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images'       => 'nullable|array',
            'images.*'     => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $shop = $this->getOrCreateShop($user);

        // Simpan gambar utama
        $mainImagePath = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_main_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('products', $filename, 'public');
            $mainImagePath = '/storage/' . $path; 
        } 
        elseif ($request->hasFile('images') && count($request->file('images')) > 0) {
            $firstFile = $request->file('images')[0];
            if ($firstFile && $firstFile->isValid()) {
                $filename = time() . '_main_cover_' . Str::slug(pathinfo($firstFile->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $firstFile->getClientOriginalExtension();
                $path = $firstFile->storeAs('products', $filename, 'public');
                $mainImagePath = '/storage/' . $path;
            }
        }

        $product = Product::create([
            'shop_id'      => $shop->id,
            'category_id'  => $request->category_id,
            'name'         => $request->name,
            'description'  => $request->description,
            'price'        => $request->price,
            'stock_status' => $request->stock_status,
            'image'        => $mainImagePath,
        ]);

        // Simpan Galeri Foto Tambahan
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
            'category_id'  => 'required|exists:categories,id',
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'stock_status' => 'required|in:ready,pre_order,out_of_stock',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images'       => 'nullable|array',
            'images.*'     => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = [
            'category_id'  => $request->category_id,
            'name'         => $request->name,
            'description'  => $request->description,
            'price'        => $request->price,
            'stock_status' => $request->stock_status,
        ];

        if ($request->hasFile('image')) {
            if ($product->image) {
                $oldFilename = basename($product->image);
                if ($oldFilename && Storage::disk('public')->exists('products/' . $oldFilename)) {
                    Storage::disk('public')->delete('products/' . $oldFilename);
                }
            }

            $file = $request->file('image');
            $filename = time() . '_main_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('products', $filename, 'public');

            $data['image'] = '/storage/' . $path;
        }

        $product->update($data);

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

        return redirect()->back()->with('success', 'Produk berhasil diperbarui!');
    }

    public function destroyImage(ProductImage $image)
    {
        $user = Auth::user();
        $shop = $user ? $this->getOrCreateShop($user) : null;

        if (!$shop || (int) $image->product?->shop_id !== (int) $shop->id) {
            abort(403, 'Akses ditolak.');
        }

        if ($image->image_path) {
            $filename = basename($image->image_path);
            if ($filename && Storage::disk('public')->exists('products/' . $filename)) {
                Storage::disk('public')->delete('products/' . $filename);
            }
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

        if ($product->image) {
            $filename = basename($product->image);
            if ($filename && Storage::disk('public')->exists('products/' . $filename)) {
                Storage::disk('public')->delete('products/' . $filename);
            }
        }

        foreach ($product->images as $img) {
            if ($img->image_path) {
                $filename = basename($img->image_path);
                if ($filename && Storage::disk('public')->exists('products/' . $filename)) {
                    Storage::disk('public')->delete('products/' . $filename);
                }
            }
        }

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

        return redirect()->back()->with('success', 'Status buka/tutup toko berhasil diperbarui!');
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

        return redirect()->back()->with('success', 'Informasi toko berhasil diperbarui!');
    }

    public function adminIndex(Request $request)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        $search = $request->input('search');

        $products = Product::with(['shop.user', 'category', 'images'])
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

        if ($product->image) {
            $filename = basename($product->image);
            if ($filename && Storage::disk('public')->exists('products/' . $filename)) {
                Storage::disk('public')->delete('products/' . $filename);
            }
        }

        foreach ($product->images as $img) {
            if ($img->image_path) {
                $filename = basename($img->image_path);
                if ($filename && Storage::disk('public')->exists('products/' . $filename)) {
                    Storage::disk('public')->delete('products/' . $filename);
                }
            }
        }

        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus dari katalog oleh Admin.');
    }
}