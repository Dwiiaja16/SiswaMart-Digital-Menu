<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ulasan extends Model
{
    use HasFactory;

    protected $table = 'ulasan';
    protected $primaryKey = 'id_ulasan';

    protected $fillable = [
        'id_produk',
        'nama_pengunjung',
        'rating',
        'komentar',
    ];

    protected $casts = [
        'rating'         => 'integer',
        'riwayat_ulasan' => 'datetime',
    ];

    /**
     * Relasi ke model Produk
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}