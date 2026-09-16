<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'name', 'email', 'password', 'role', 'whatsapp_number', 'is_suspended', 'google_id', 'login_method'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Set Nilai Default Atribut saat User Baru Dibuat
     */
    protected $attributes = [
        'role'         => 'user',  // KUNCI UTAMA: Wajib default 'user' (Pembeli/Pengunjung)
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
}