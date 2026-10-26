<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';
    protected $primaryKey = 'id_produk';

    // SESUAI ERD FINAL DENGAN TEPAT
    protected $fillable = [
        'username',
        'id_kategori',
        'nama_produk',
        'deskripsi',
        'harga',
        'foto_produk',
        'status_stok',
        'status_moderasi',
        'views',
    ];

    protected $casts = [
        'harga' => 'float',
        'views' => 'integer',
    ];

    // Sertakan otomatis ke JSON Inertia Frontend
    protected $appends = ['foto_produk_url', 'semua_foto_url'];

    /**
     * Accessor: Mengambil SELURUH foto (Cover + Pendukung) dalam bentuk Array URL yang bersih
     */
    public function getSemuaFotoUrlAttribute(): array
    {
        if (empty($this->foto_produk)) {
            return [];
        }

        $raw = $this->foto_produk;
        $list = [];

        // Cek apakah berupa Array atau JSON String
        if (is_array($raw)) {
            $list = $raw;
        } else {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $list = $decoded;
            } else {
                $list = [$raw];
            }
        }

        $cleanUrls = [];
        foreach ($list as $path) {
            if (!$path || !is_string($path)) continue;

            $val = trim($path);
            $lower = strtolower($val);

            if ($val === '' || $lower === 'null' || $lower === 'undefined') continue;

            // Jika sudah berbentuk URL HTTP/HTTPS/Data-URI
            if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, 'data:image')) {
                $cleanUrls[] = $val;
                continue;
            }

            // Bersihkan sisa-sisa karakter JSON jika ada
            $val = str_replace(['[', ']', '"', "'"], '', $val);
            $filename = basename($val);

            if (!empty($filename) && strtolower($filename) !== 'null' && strtolower($filename) !== 'undefined') {
                $cleanUrls[] = '/storage/products/' . $filename;
            }
        }

        return array_values($cleanUrls);
    }

    /**
     * Accessor: Mengambil Foto Utama / Cover saja
     */
    public function getFotoProdukUrlAttribute(): ?string
    {
        $allPhotos = $this->semua_foto_url;
        if (!empty($allPhotos)) {
            return $allPhotos[0];
        }

        return asset('images/default-product.png');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    public function dataPenjual(): BelongsTo
    {
        return $this->belongsTo(DataPenjual::class, 'username', 'username');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class, 'id_produk', 'id_produk');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'id_produk', 'id_produk');
    }
}