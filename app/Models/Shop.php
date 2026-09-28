<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_open',
        'status',
        'last_status_change_at',
        'avatar_path',
    ];

    protected $casts = [
        'is_open'               => 'boolean',
        'last_status_change_at' => 'datetime',
    ];

    protected $appends = ['avatar_url'];

    /**
     * Accessor aman untuk avatar_url dengan fallback null-safe
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!empty($this->avatar_path)) {
            if (str_starts_with($this->avatar_path, 'http')) {
                return $this->avatar_path;
            }
            return asset('storage/' . ltrim($this->avatar_path, '/'));
        }
        return null;
    }

    /**
     * Scope: hanya toko yang tidak di-suspend
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Helper: apakah toko sedang di-suspend?
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Relasi balik ke model User (Pemilik Lapak / Penjual)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke model Product (Daftar Produk milik Lapak)
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}