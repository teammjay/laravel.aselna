<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreNavigationPage extends Model
{
    // Set a custom table name
    protected $table = 'store_navigation_page';

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

    protected $fillable = [
        'id',
        'store_id',
        'store_image_url',
        'page_type',
        'page_id',
        'url_path',
        'parent_page_id',
        'template_id',
        'title',
        'device_brand_id',
        'device_id',
        'product_type_id',
        'generated_body',
        'keywords',
        'meta_title',
        'meta_description',
        'child_ids',
        'description',
        'image_id',
        'synchronize',
        'update_image'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
}
