<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Kolom yang dapat diisi secara mass-assignment
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role',
        'whatsapp_number',
        'is_suspended',
        'google_id',
        'login_method',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Nilai default atribut saat record baru dibuat
     */
    protected $attributes = [
        'role'         => 'user',  // Default: 'user' (Pembeli / Pengunjung)
        'is_suspended' => false,
    ];

    /**
     * Cast Atribut
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_suspended'      => 'boolean',
        ];
    }

    /**
     * Relasi ke model Shop (Hanya dimiliki jika role = penjual)
     */
    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class);
    }

    /**
     * Relasi ke Ulasan Produk
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Helper Method untuk Pengecekan Role Pengguna
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPenjual(): bool
    {
        return $this->role === 'penjual';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function sellerProfile()
    {
        return $this->hasOne(SellerProfile::class);
    }
}