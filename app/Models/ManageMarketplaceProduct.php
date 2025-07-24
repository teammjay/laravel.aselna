<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ManageMarketplaceProduct extends Model
{
    // Set a custom table name
    protected $table = 'manage_marketplace_products';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }


    protected $fillable = ['id', 'product_id', 'product_code', 'store_id', 'device_id', 'product_code','parent_id', 'sku', 'upc_code', 'marketplace_product_id', 'marketplace_variant_id', 'upload_status', 'upload_attempts', 'created_at', 'updated_at'];
}
