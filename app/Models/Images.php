<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Images extends Model
{
    use HasFactory;

    protected $fillable = ['file_path', 'original_name', 'reference_id'];

    // Automatically generates a UUID for the reference_id when creating a new image
    protected static function booted()
    {
        static::creating(function ($image) {
            $image->reference_id = Str::uuid()->toString();
        });
    }
}
