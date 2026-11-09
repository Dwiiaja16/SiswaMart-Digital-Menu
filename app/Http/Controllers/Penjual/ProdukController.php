<?php

/**
 * HTTP endpoints for seller-owned product catalog operations.
 */

namespace App\Http\Controllers\Penjual;

use App\Http\Controllers\Controller;
use App\Http\Controllers\KatalogController; // <-- FIX: Import KatalogController di sini
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Manages seller product listings and their uploaded image assets.
 *
 * Product changes are scoped to the authenticated seller, and newly created
 * listings require administrator moderation before appearing in the catalog.
 */
class ProdukController extends Controller
{
    /**
     * Show the authenticated seller's products and optional category/name filters.
     *
     * @param Request $request Request carrying the optional kategori_id and search filters.
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse Product-management page, or redirect to login when unauthenticated.
     * @precondition The caller should be an authenticated seller; unauthenticated requests are redirected to login.
     * @side-effects Loads the seller's products with categories and reviews; does not modify records.
     */
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
            // Seller search intentionally follows the product manager's prefix-match rule.
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

    /**
     * Delegate product-detail rendering to the public catalog controller.
     *
     * @param int|string $id_produk Product primary key from the route.
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse Catalog detail response returned by the delegated controller.
     * @precondition The product must be resolvable by the catalog controller.
     * @side-effects Inherits the catalog controller's view-count behavior, if applicable.
     */
    public function show($id_produk)
    {
        // Alternatif jika tanpa use di atas: return app(\App\Http\Controllers\KatalogController::class)->show($id_produk);
        return app(KatalogController::class)->show($id_produk);
    }

    /**
     * Create a product listing and store its optional cover/gallery images.
     *
     * @param Request $request Validated product attributes and optional uploaded image files.
     * @return \Illuminate\Http\RedirectResponse Redirect with a success message or validation/storage error feedback.
     * @precondition The seller must be authenticated and have a non-placeholder shop name; product fields and images must satisfy validation rules.
     * @side-effects Stores uploaded files, creates a pending product, and attempts to create a moderation notification; notification failures are logged.
     * @throws \Illuminate\Validation\ValidationException When request data fails validation.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

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

            if ($request->hasFile('foto_produk')) {
                $file = $request->file('foto_produk');
                $filename = time() . '_cover_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('products', $filename, 'public');
                $allPhotos[] = '/storage/' . $path;
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $idx => $file) {
                    $filename = time() . '_gallery_' . $idx . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('products', $filename, 'public');
                    $allPhotos[] = '/storage/' . $path;
                }
            }

            // Keep single-image records readable by older code while storing galleries as JSON.
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

    /**
     * Update a seller's product details and reconcile its stored image gallery.
     *
     * @param Request $request Validated product attributes, retained image paths, and optional new image files.
     * @param int|string $id_produk Product primary key from the route.
     * @return \Illuminate\Http\RedirectResponse Redirect with a success message or validation feedback.
     * @precondition The product must exist and belong to the authenticated seller unless the caller is an administrator; submitted fields must pass validation.
     * @side-effects Deletes removed image files, stores replacement/additional files, updates the product, and returns a rejected listing to pending moderation.
     * @throws \Illuminate\Validation\ValidationException When request data fails validation.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException When the caller is not the product owner or an administrator.
     */
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
            // A corrected rejected listing must be reviewed again before returning to the catalog.
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
                        // Remove discarded assets so edits do not leave orphaned public files.
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

    /**
     * Soft-delete a product while preserving its historical record and related reporting context.
     *
     * @param int|string $id_produk Product primary key from the route.
     * @return \Illuminate\Http\RedirectResponse Redirect with a success message.
     * @precondition The product must exist and belong to the authenticated seller unless the caller is an administrator.
     * @side-effects Sets the product's deleted_at timestamp so default catalog queries no longer return it.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException When the caller is not the product owner or an administrator.
     */
    public function destroy($id_produk)
    {
        $user = Auth::user();
        $produk = Produk::where('id_produk', $id_produk)->firstOrFail();

        if ($produk->username !== $user->username && !$user->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        // Preserve report history while removing the listing from default catalog queries.
        $produk->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus!');
    }
}