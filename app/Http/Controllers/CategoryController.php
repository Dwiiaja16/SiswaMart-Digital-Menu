<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CategoryController extends Controller
{
    /**
     * Menampilkan daftar kategori untuk Admin
     */
    public function index()
    {
        $categories = Category::withCount('products')->latest()->get();
        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Menyimpan kategori baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255|unique:categories,name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $data = [
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']) ?: 'cat-' . time(),
        ];

        // Safe Guard: Hanya proses dan simpan 'image' jika kolomnya sudah ada di tabel MySQL
        if (Schema::hasColumn('categories', 'image')) {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $uploadPath = public_path('categories');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true, true);
                }

                $file = $request->file('image');
                $filename = time() . '_cat_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                $file->move($uploadPath, $filename);
                $imagePath = '/categories/' . $filename;
            }
            $data['image'] = $imagePath;
        }

        Category::create($data);

        return redirect()->back()->with('success', 'Kategori baru berhasil ditambahkan!');
    }

    /**
     * Mengubah data kategori
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255|unique:categories,name,' . $category->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $data = [
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']) ?: 'cat-' . time(),
        ];

        // Safe Guard: Pengecekan kolom 'image' untuk update
        if (Schema::hasColumn('categories', 'image')) {
            if ($request->hasFile('image')) {
                if ($category->image && !str_starts_with($category->image, 'http')) {
                    $oldPath = public_path(ltrim($category->image, '/'));
                    if (File::exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $uploadPath = public_path('categories');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true, true);
                }

                $file = $request->file('image');
                $filename = time() . '_cat_' . preg_replace('/[^a-zA-Z0-9._-]/}{', '_', $file->getClientOriginalName());
                $file->move($uploadPath, $filename);
                $data['image'] = '/categories/' . $filename;
            }
        }

        $category->update($data);

        return redirect()->back()->with('success', 'Kategori berhasil diperbarui!');
    }

    /**
     * Menghapus kategori
     */
    public function destroy(Category $category)
    {
        // Pengecekan tipe relasi secara aman (HasMany maupun BelongsToMany/Pivot)
        if (method_exists($category->products(), 'detach')) {
            $category->products()->detach();
        } else {
            $category->products()->update(['category_id' => null]);
        }

        // Hapus fisik gambar kategori jika kolom image ada
        if (Schema::hasColumn('categories', 'image') && $category->image && !str_starts_with($category->image, 'http')) {
            $oldPath = public_path(ltrim($category->image, '/'));
            if (File::exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $category->delete();

        return redirect()->back()->with('success', 'Kategori berhasil dihapus!');
    }
}