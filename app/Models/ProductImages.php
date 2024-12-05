<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductImages extends Model
{
    // Set a custom table name
    protected $table = 'product_images';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = ['product_id','image', 'thumbnail_image','sort_order','created_by','updated_by','created_at','updated_at'];
}
