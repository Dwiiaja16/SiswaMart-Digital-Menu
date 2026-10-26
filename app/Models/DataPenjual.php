<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataPenjual extends Model
{
    use HasFactory;

    protected $table = 'data_penjual';
    protected $primaryKey = 'id_penjual';

    protected $fillable = [
        'username',
        'nama_lengkap',
        'nama_toko',
        'nama_kelas',
        'nisn',
        'foto_penjual',
        'alamat',
        'created_by_admin_id',
        'email',
        'nomor_whatsapp',
        'status_toko',
    ];

    /**
     * Relasi ke User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    /**
     * Relasi ke Admin pembuat akun
     */
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id', 'user_id');
    }

    /**
     * Relasi ke Produk milik Penjual
     */
    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'username', 'username');
    }
}