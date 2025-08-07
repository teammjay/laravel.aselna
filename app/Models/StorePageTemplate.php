<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StorePageTemplate extends Model
{
    protected $table = 'store_page_template';

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
        'name',
        'overwrite',
        'page_type',
        'template_code',
        'meta_description_template',
        'meta_title_template',
        'meta_keywords_template',
        'title_template',
        'description_template',
        'store_id',
        'product_type_id'
    ];

    public function store(){
        return $this->belongsTo(Store::class,'store_id');
    }

    public function PageType(){
        return $this->belongsTo(PageType::class,'page_type');
    }

    public function productType(){
        return $this->belongsTo(ProductType::class,'product_type_id');
    }
}
