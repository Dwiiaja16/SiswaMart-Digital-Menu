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
     * Nama tabel yang terhubung dengan model ini.
     */
    protected $table = 'users';

    /**
     * Primary key dari tabel users.
     */
    protected $primaryKey = 'user_id';

    /**
     * Kolom yang dapat diisi secara mass-assignment.
     */
    protected $fillable = [
        'username',
        'password',
        'role',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Nilai default atribut saat record baru dibuat.
     */
    protected $attributes = [
        'role' => 'penjual',
    ];

    /**
     * Cast Atribut.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke Data Penjual (One to One berdasarkan username)
     */
    public function dataPenjual(): HasOne
    {
        return $this->hasOne(DataPenjual::class, 'username', 'username');
    }

    /**
     * Relasi ke Produk (One to Many berdasarkan username)
     */
    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'username', 'username');
    }

    /**
     * Relasi ke Notifikasi (One to Many berdasarkan username)
     */
    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'username', 'username');
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
}