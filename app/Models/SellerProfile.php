<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'class_name',
        'nisn',
        'photo_path',
        'address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}