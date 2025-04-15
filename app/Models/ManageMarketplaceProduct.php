<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ManageMarketplaceProduct extends Model
{
    // Set a custom table name
    protected $table = 'manage_shopify_products';

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


    protected $fillable = ['id', 'product_id', 'store_id', 'product_code', 'sku', 'shopify_product_id', 'shopify_variant_id', 'upload_status', 'upload_attempts', 'created_at', 'updated_at'];
}
