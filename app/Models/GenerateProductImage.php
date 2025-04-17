<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GenerateProductImage extends Model
{
    protected $table = 'generated_product_image';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }

    protected $fillable = [
        'id',
        'product_id',
        'product_image_id',
        'alt_product_image_id',
        'main_image',
        'version_number',
        'image_file_system_id',
        'path',
        'reference_id',
    ];

    public function productImage()
    {
        if ($this->product_image_id) {
            return $this->belongsTo(ProductImages::class, 'product_image_id');
        }

        return $this->belongsTo(ProductImageAlternative::class, 'alt_product_image_id');
    }
}
