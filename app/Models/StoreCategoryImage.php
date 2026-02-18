<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreCategoryImage extends Model
{
    protected $table = 'store_category_image';

    public $timestamps = true;

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

    protected $fillable = ['store_id','category_id','image_id'];

    public function store()
    {
        return $this->belongsTo(Store::class,'store_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductType::class,'category_id');
    }

    public function imageObj()
    {
        return $this->belongsTo(SysFile::class, 'image_id');
    }
}
