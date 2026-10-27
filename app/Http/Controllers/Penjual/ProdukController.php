<?php

namespace App\Http\Controllers\Penjual;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $categoryId = $request->input('kategori_id');
        $search = $request->input('search');

        $products = Produk::where('username', $user->username)
            ->with(['kategori', 'ulasan'])
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('id_kategori', $categoryId);
            })
            // Filter database berdasarkan HURUF DEPAN nama produk
            ->when($search, function ($query, $search) {
                $query->where('nama_produk', 'LIKE', $search . '%');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Kategori::all();

        return Inertia::render('Penjual/Produk/Index', [
            'products'   => $products,
            'categories' => $categories,
            'seller'     => $user->dataPenjual,
            'filters'    => [
                'kategori_id' => $categoryId ?? '',
                'search'      => $search ?? '',
            ],
        ]);
    }

    public function show($id_produk)
    {
        return app(KatalogController::class)->show($id_produk);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Cek Nama Toko Penjual (Satu baris pengaman ganda)
        $penjual = $user->dataPenjual;
        if (!$penjual || empty(trim($penjual->nama_toko)) || trim($penjual->nama_toko) === '-') {
            return redirect()->back()->withErrors([
                'nama_toko' => 'Kamu wajib mengisi Nama Toko Usaha terlebih dahulu sebelum menambahkan produk!'
            ]);
        }

        $request->validate([
            'id_kategori' => 'required|exists:kategori,id_kategori',
            'nama_produk' => 'required|string|max:255',
            'deskripsi'   => 'required|string',
            'harga'       => 'required|numeric|min:0',
            'status_stok' => 'required|in:ready,pre-order',
            'foto_produk' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images'      => 'nullable|array',
            'images.*'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'id_kategori.required' => 'Silakan pilih 1 kategori jajanan.',
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'deskripsi.required'   => 'Deskripsi singkat wajib diisi.',
            'harga.required'       => 'Harga produk wajib diisi.',
        ]);

        try {
            $allPhotos = [];

            // 1. Simpan Foto Utama / Cover
            if ($request->hasFile('foto_produk')) {
                $file = $request->file('foto_produk');
                $filename = time() . '_cover_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('products', $filename, 'public');
                $allPhotos[] = '/storage/' . $path;
            }

            // 2. Simpan Foto Pendukung / Galeri (Array Images)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $idx => $file) {
                    $filename = time() . '_gallery_' . $idx . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('products', $filename, 'public');
                    $allPhotos[] = '/storage/' . $path;
                }
            }

            // Format Penyimpanan
            $fotoPath = null;
            if (count($allPhotos) === 1) {
                $fotoPath = $allPhotos[0];
            } elseif (count($allPhotos) > 1) {
                $fotoPath = json_encode(array_values($allPhotos));
            }

            $produk = Produk::create([
                'username'        => $user->username,
                'id_kategori'     => $request->id_kategori,
                'nama_produk'     => trim($request->nama_produk),
                'deskripsi'       => trim($request->deskripsi),
                'harga'           => $request->harga,
                'status_stok'     => $request->status_stok,
                'status_moderasi' => 'pending',
                'foto_produk'     => $fotoPath,
            ]);

            try {
                Notifikasi::create([
                    'username'  => $user->username,
                    'id_produk' => $produk->id_produk,
                    'judul'     => 'Produk Baru Menunggu Moderasi',
                    'pesan'     => "Toko '{$penjual->nama_toko}' mengunggah produk '{$produk->nama_produk}'. Silakan periksa.",
                    'is_read'   => false,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal membuat notifikasi: ' . $e->getMessage());
            }

            return redirect()->back()->with('success', 'Produk berhasil diunggah! Menunggu persetujuan Admin.');
            
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'nama_produk' => 'Gagal menyimpan ke database: ' . $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id_produk)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $produk = Produk::where('id_produk', $id_produk)->firstOrFail();

        if ($produk->username !== $user->username && !$user->isAdmin()) {
            abort(403, 'Akses ditolak. Anda bukan pemilik produk ini.');
        }

        $request->validate([
            'id_kategori'     => 'required|exists:kategori,id_kategori',
            'nama_produk'     => 'required|string|max:255',
            'deskripsi'       => 'required|string',
            'harga'           => 'required|numeric|min:0',
            'status_stok'     => 'required|in:ready,pre-order',
            'foto_produk'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images'          => 'nullable|array',
            'images.*'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'retained_photos' => 'nullable',
        ]);

        $data = [
            'id_kategori' => $request->id_kategori,
            'nama_produk' => trim($request->nama_produk),
            'deskripsi'   => trim($request->deskripsi),
            'harga'       => $request->harga,
            'status_stok' => $request->status_stok,
        ];

        if ($produk->status_moderasi === 'ditolak') {
            $data['status_moderasi'] = 'pending';
        }

        $currentPhotos = [];
        if (!empty($produk->foto_produk)) {
            $decoded = json_decode($produk->foto_produk, true);
            if (is_array($decoded)) {
                $currentPhotos = $decoded;
            } else {
                $currentPhotos = [$produk->foto_produk];
            }
        }

        if ($request->has('retained_photos')) {
            $retained = $request->input('retained_photos');
            if (is_string($retained)) {
                $retained = json_decode($retained, true) ?? [];
            }
            if (is_array($retained)) {
                foreach ($currentPhotos as $photo) {
                    if (!in_array($photo, $retained)) {
                        $filename = basename($photo);
                        if ($filename && Storage::disk('public')->exists('products/' . $filename)) {
                            Storage::disk('public')->delete('products/' . $filename);
                        }
                    }
                }
                $currentPhotos = array_values(array_filter($currentPhotos, function($p) use ($retained) {
                    return in_array($p, $retained);
                }));
            }
        }

        if ($request->hasFile('foto_produk')) {
            if (!empty($currentPhotos[0])) {
                $oldCoverFilename = basename($currentPhotos[0]);
                if ($oldCoverFilename && Storage::disk('public')->exists('products/' . $oldCoverFilename)) {
                    Storage::disk('public')->delete('products/' . $oldCoverFilename);
                }
            }

            $file = $request->file('foto_produk');
            $filename = time() . '_cover_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('products', $filename, 'public');
            $newCover = '/storage/' . $path;

            if (count($currentPhotos) > 0) {
                $currentPhotos[0] = $newCover;
            } else {
                array_unshift($currentPhotos, $newCover);
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $idx => $file) {
                $filename = time() . '_gallery_' . $idx . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('products', $filename, 'public');
                $currentPhotos[] = '/storage/' . $path;
            }
        }

        $finalPhotos = array_values($currentPhotos);

        if (count($finalPhotos) === 0) {
            $data['foto_produk'] = null;
        } elseif (count($finalPhotos) === 1) {
            $data['foto_produk'] = $finalPhotos[0];
        } else {
            $data['foto_produk'] = json_encode($finalPhotos);
        }

        $produk->update($data);

        return redirect()->back()->with('success', 'Produk berhasil diperbarui!');
    }

    public function destroy($id_produk)
    {
        $user = Auth::user();
        $produk = Produk::where('id_produk', $id_produk)->firstOrFail();

        if ($produk->username !== $user->username && !$user->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        // Jalankan Soft Delete (Menutupi dari katalog publik dan mengisi deleted_at)
        $produk->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus!');
    }
}