<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'category_id', // Kunci 1 Kategori
        'name',
        'description',
        'price',
        'stock_status',
        'views_count',
        'image',
    ];

    protected $casts = [
        'price'       => 'float',
        'views_count' => 'integer',
    ];

    protected $appends = ['image_url'];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) return null;

                $val = trim((string) $value);
                $lower = strtolower($val);

                if ($val === '' || $lower === 'null' || $lower === 'undefined') return null;
                if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, 'data:image')) return $val;

                $filename = basename($val);
                if (empty($filename) || strtolower($filename) === 'null' || strtolower($filename) === 'undefined') return null;

                return '/storage/products/' . $filename;
            },
            set: function ($value) {
                if (empty($value)) return null;

                $val = trim((string) $value);
                $lower = strtolower($val);

                if ($val === '' || $lower === 'null' || $lower === 'undefined') return null;
                if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, 'data:image')) return $val;

                $filename = basename($val);
                if (empty($filename) || strtolower($filename) === 'null' || strtolower($filename) === 'undefined') return null;

                return '/storage/products/' . $filename;
            }
        );
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return $this->image;
        }

        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $first = $this->images->first();
            if ($first && $first->image_path) {
                return $first->image_path;
            }
        }

        return asset('images/default-product.png');
    }

    // Relasi Tunggal (1 Produk Memiliki 1 Kategori)
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order', 'asc');
    }
}