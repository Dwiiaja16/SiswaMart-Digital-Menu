<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image_path', 'sort_order'];

    /**
     * Accessor & Mutator untuk field image_path
     */
    protected function imagePath(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) {
                    return null;
                }

                $val = trim((string) $value);
                $lower = strtolower($val);

                if ($val === '' || $lower === 'null' || $lower === 'undefined') {
                    return null;
                }

                if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, 'data:image')) {
                    return $val;
                }

                $filename = basename($val);
                if (empty($filename) || strtolower($filename) === 'null' || strtolower($filename) === 'undefined') {
                    return null;
                }

                return '/storage/products/' . $filename;
            },
            set: function ($value) {
                if (empty($value)) {
                    return null;
                }

                $val = trim((string) $value);
                $lower = strtolower($val);

                if ($val === '' || $lower === 'null' || $lower === 'undefined') {
                    return null;
                }

                if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, 'data:image')) {
                    return $val;
                }

                $filename = basename($val);
                if (empty($filename) || strtolower($filename) === 'null' || strtolower($filename) === 'undefined') {
                    return null;
                }

                return '/storage/products/' . $filename;
            }
        );
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
