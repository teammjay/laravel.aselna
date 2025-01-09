<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Catalog extends Model
{
    // Set a custom table name
    protected $table = 'products';

    // Custom primary key
    protected $primaryKey = 'product_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->product_id)) {
                $model->product_id = (string)Str::uuid();
            }
        });
    }


    protected $fillable = ['product_id', 'product_title', 'product_type','configurable', 'manufacturer_id', 'product_price', 'product_wholesale_price', 'product_marketplace_price', 'product_mrsp', 'dimensions_width', 'dimensions_height', 'dimensions_length', 'product_weight', 'product_weight_unit', 'product_package_weight', 'package_weight_unit', 'package_dimensions_width', 'package_dimensions_height', 'package_dimensions_length', 'product_sku', 'product_description', 'product_status', 'product_condition_id', 'target_aud', 'product_tags', 'product_color', 'product_qty', 'product_reorder_qty', 'product_pick_location', 'title_template', 'description_template', 'product_code', 'created_by', 'updated_by', 'oem_generic','product_image_url'];


    public function Manufacturer()
    {
        return $this->belongsTo(Manufacturer::class,'manufacturer_id');
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class,'product_type');
    }

    public function condition()
    {
        return $this->belongsTo(ProductCondition::class,'product_condition_id');
    }

    public function StoreProduct()
    {
        return $this->hasOne(StoreProduct::class, 'product_id','product_id');
    }
}
