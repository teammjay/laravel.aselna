<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListingItemDetail extends Model
{
    // Set a custom table name
    protected $table = 'listing_item_detail';

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


    protected $fillable = ['sales_count','sku','upc','title','kicked','replaced_id','device_id','device_carrier_id','listing_item_id','last_sale_date','url','store_sku','created_by','updated_by','created_at','updated_at'];
}
