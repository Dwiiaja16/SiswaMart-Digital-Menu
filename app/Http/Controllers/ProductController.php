<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Tampilkan daftar produk milik lapak penjual yang sedang login
    public function index()
    {
        $user = Auth::user();
        $shop = $user->shop;

        // Jika penjual belum punya shop, buatkan otomatis
        if (!$shop) {
            $shop = Shop::create([
                'user_id' => $user->id,
                'name'    => 'Lapak ' . $user->name,
                'is_open' => true,
            ]);
        }

        // Load produk beserta relasi categories (plural)
        $products = $shop->products()->with('categories')->latest()->get();
        $categories = Category::all();

        return Inertia::render('Penjual/Products/Index', [
            'shop'       => $shop,
            'products'   => $products,
            'categories' => $categories,
        ]);
    }

    // Tampilkan detail produk untuk pengunjung / pembeli
    public function show($id)
    {
        // Load produk beserta relasi shop, user pemilik shop, categories, dan reviews
        $product = Product::with([
            'shop.user', 
            'categories', 
            'reviews.user'
        ])->findOrFail($id);

        $currentUser = Auth::user();

        // 1. Cek Pemilik Lapak (Penjual)
        $isOwner = false;
        if ($currentUser && $currentUser->shop) {
            $isOwner = (int)$currentUser->shop->id === (int)$product->shop_id;
        }

        // 2. Cek apakah user pembeli sudah pernah mengulas produk ini
        $hasReviewed = false;
        if ($currentUser && $product->reviews) {
            $hasReviewed = $product->reviews->contains('user_id', $currentUser->id);
        }

        return Inertia::render('Catalog/Show', [
            'product'     => $product,
            'isOwner'     => $isOwner,
            'hasReviewed' => $hasReviewed,
        ]);
    }

    // Tambah produk baru
    public function store(Request $request)
    {
        // 1. Cek status suspend di paling awal!
        if (Auth::user()->is_suspended) {
            return redirect()->back()->with('error', 'Akun kamu sedang di-suspend. Tidak dapat menambah atau mengubah produk.');
        }

        $request->validate([
            'category_ids'   => 'required|array|min:1', // Menerima array ID kategori (minimal 1 dipilih)
            'category_ids.*' => 'exists:categories,id',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock_status'   => 'required|in:ready,pre_order,out_of_stock',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $shop = Auth::user()->shop;

        // 2. Simpan gambar secara manual ke folder public/products/
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            $file->move(public_path('products'), $filename);
            
            // Format URL bersih yang bisa langsung dipanggil Vue browser
            $imagePath = '/products/' . $filename;
        }

        $product = Product::create([
            'shop_id'      => $shop->id,
            'name'         => $request->name,
            'description'  => $request->description,
            'price'        => $request->price,
            'stock_status' => $request->stock_status,
            'image'        => $imagePath,
        ]);

        // 3. Attach array ID kategori ke tabel pivot category_product
        $product->categories()->attach($request->category_ids);

        Notification::create([
            'product_id' => $product->id,
            'type'       => 'produk_baru',
        ]);

        return redirect()->back()->with('success', 'Produk berhasil ditambahkan!');
    }

    // Update produk
    public function update(Request $request, Product $product)
    {
        // 1. Cek otorisasi & status suspend
        if ($product->shop_id !== Auth::user()->shop->id) {
            abort(403);
        }

        if (Auth::user()->is_suspended) {
            return redirect()->back()->with('error', 'Akun kamu sedang di-suspend. Tidak dapat menambah atau mengubah produk.');
        }

        $request->validate([
            'category_ids'   => 'required|array|min:1', // Menerima array ID kategori
            'category_ids.*' => 'exists:categories,id',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock_status'   => 'required|in:ready,pre_order,out_of_stock',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = [
            'name'         => $request->name,
            'description'  => $request->description,
            'price'        => $request->price,
            'stock_status' => $request->stock_status,
        ];

        // 2. Update gambar ke folder public/products/
        if ($request->hasFile('image')) {
            // Hapus foto lama dari public_path jika ada
            if ($product->image) {
                $oldPath = public_path(ltrim($product->image, '/'));
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $file = $request->file('image');
            $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            $file->move(public_path('products'), $filename);

            $data['image'] = '/products/' . $filename;
        }

        $product->update($data);

        // 3. Sync kategori baru (kategori lama otomatis disesuaikan di tabel pivot)
        $product->categories()->sync($request->category_ids);

        return redirect()->back()->with('success', 'Produk berhasil diperbarui!');
    }

    // Hapus produk
    public function destroy(Product $product)
    {
        if ($product->shop_id !== Auth::user()->shop->id) {
            abort(403);
        }

        // Hapus file fisik gambar jika ada
        if ($product->image) {
            $oldPath = public_path(ltrim($product->image, '/'));
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus!');
    }

    // Toggle status lapak (buka/tutup)
    public function toggleShopStatus(Request $request)
    {
        $shop = Auth::user()->shop;

        if ($shop) {
            $shop->update([
                'is_open' => !$shop->is_open,
            ]);
        }

        return redirect()->back()->with('success', 'Status lapak berhasil diperbarui!');
    }

    // Penjual mengedit nama & deskripsi lapak miliknya sendiri
    public function updateShop(Request $request)
    {
        $shop = Auth::user()->shop;

        if (!$shop) {
            abort(404, 'Lapak tidak ditemukan.');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $shop->update($validated);

        return redirect()->back()->with('success', 'Nama lapak berhasil diperbarui!');
    }

    public function adminIndex(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        $search = $request->input('search');

        $products = Product::with(['shop.user', 'categories'])
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
            'filters'  => ['search' => $search],
        ]);
    }

    // Hapus Produk Pelanggaran oleh Admin
    public function adminDestroy(Product $product)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Aksi ini hanya untuk Admin.');
        }

        // Hapus foto produk dari storage jika bukan URL eksternal
        if ($product->image && !str_starts_with($product->image, 'http')) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus dari katalog.');
    }
}