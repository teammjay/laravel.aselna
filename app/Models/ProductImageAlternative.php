<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductImageAlternative extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }

    protected $fillable = ['id', 'product_id', 'main_image_id', 'image', 'version_number', 'thumbnail_image', 'sort_order', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    public function product()
    {
        return $this->belongsTo(Catalog::class, 'product_id');
    }

    public function mainImage()
    {
        return $this->belongsTo(ProductImages::class, 'main_image_id', 'id');
    }
	
    public function imageObj()
    {
        return $this->belongsTo(SysFile::class, 'image');
    }
}
