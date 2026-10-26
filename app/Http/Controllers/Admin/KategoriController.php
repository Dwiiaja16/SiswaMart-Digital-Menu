<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class KategoriController extends Controller
{
    public function index()
    {
        $categories = Kategori::withCount('produk')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Kategori/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
        ], [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.unique'   => 'Nama kategori ini sudah terdaftar.',
        ]);

        try {
            Kategori::create([
                'nama_kategori' => trim($validated['nama_kategori']),
            ]);

            return redirect()->back()->with('success', 'Kategori baru berhasil ditambahkan!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'nama_kategori' => 'Gagal simpan DB: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $kategori = Kategori::where('id_kategori', $id)->firstOrFail();

        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori,' . $kategori->id_kategori . ',id_kategori',
        ], [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.unique'   => 'Nama kategori ini sudah terdaftar.',
        ]);

        try {
            $kategori->update([
                'nama_kategori' => trim($validated['nama_kategori']),
            ]);

            return redirect()->back()->with('success', 'Kategori berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'nama_kategori' => 'Gagal update: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $kategori = Kategori::where('id_kategori', $id)->firstOrFail();
            $katId = $kategori->id_kategori;

            // Cek jumlah produk yang masih menggunakan kategori ini
            $produkCount = DB::table('produk')->where('id_kategori', $katId)->count();

            if ($produkCount > 0) {
                // Buat atau cari Kategori default "Umum"
                $defaultCategory = Kategori::firstOrCreate(
                    ['nama_kategori' => 'Umum']
                );

                // Jika kategori yang mau dihapus adalah kategori "Umum" itu sendiri
                if ($defaultCategory->id_kategori == $katId) {
                    return redirect()->back()->withErrors([
                        'error' => 'Kategori "Umum" tidak dapat dihapus karena sedang digunakan sebagai kategori fallback produk.'
                    ]);
                }

                // Alihkan semua produk ke kategori "Umum"
                DB::table('produk')
                    ->where('id_kategori', $katId)
                    ->update(['id_kategori' => $defaultCategory->id_kategori]);
            }

            // Hapus kategori yang diinginkan
            $kategori->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Kategori berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors([
                'error' => 'Gagal menghapus kategori: ' . $e->getMessage()
            ]);
        }
    }
}