<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListingItem extends Model
{
    // Set a custom table name
    protected $table = 'listing_item';

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


    protected $fillable = ['id','store_type_listing_id','title','description','sku','store_sku','store_url','parent_id','price','sync_status','processing_days','quantity','creation_order','creation_retry_count','linked_item_id','carrier_id','device_id','store_product_id','generation_type_id','brand_id','device_carrier_id','color_id','size_id','status','client','upc','brand_combo','carrier_combo','submit_count','replaced_id','sync_message','mpn','manufacturer_id','sales_count','last_sale_date','image_version','fulfillment_type','overwrite_price','overwrite_msrp','overwrite_compare_at_price','overwrite_title','overwrite_description','created_by','updated_by','created_at','updated_at'];

    public function storeTypeListing()
    {
        return $this->belongsTo(StoreTypeListing::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class,'brand_id');
    }

    public function device()
    {
        return $this->belongsTo(Brand::class,'device_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function devices()
    {
        //listing_item_detail.listing_item_id = listing_item.id
        return $this->hasMany(ListingItemDetail::class,'listing_item_id','id');
    }

    public function storeProduct()
    {
        return $this->belongsTo(StoreProduct::class, 'store_product_id');
    }

    public function products()
    {
        return $this->hasMany(Catalog::class);
    }

    //maunfacturer
    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function generationType()
    {
        return $this->belongsTo(ListingGenerationType::class, 'generation_type_id','code');
    }

}
