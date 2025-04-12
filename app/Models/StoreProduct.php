<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreProduct extends Model
{
    // Set a custom table name
    protected $table = 'store_product';

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

    /*protected $fillable = ['product_id','store_id','store_category_id','title','active','errors','profit_rate','client','price','msrp','description','channel_category_id','sales_count','last_sale_date','include_carriers','brand_combo','carrier_combo','manufacturer_id','listing_generation_type_id','listing_title_template_id','preserve_title_description','fulfillment_by_channel_price','last_update_date_time','last_synch_date_time','overwrite_compare_at_price','wholesale_price','compare_at_price','long_tail_keywords','short_tail_keywords','fulfillment_type','synch_price','synch_inventory','synch_item','overwrite_wholesale_price','overwrite_description','overwrite_title','overwrite_fulfillment_by_channel_price','overwrite_msrp','overwrite_price','created_by','updated_by'];*/

    protected $fillable = ['id','product_id','store_id','store_type_category_id','title','active','price','msrp','description','sales_count','manufacturer_id','overwrite_compare_at_price','wholesale_price','compare_at_price','overwrite_wholesale_price','overwrite_description','overwrite_title','overwrite_msrp','overwrite_price','created_by','updated_by','created_at','updated_at'];

    public static function getStoreProductDetail($product_id){
        $query2 = self::select('store_product.*','products.product_sku','store_product.created_at','product_type.name as ProductType')->with('Product');
        $query2->leftJoin('products','products.product_id', '=', 'store_product.product_id');
        $query2->leftJoin('product_type','products.product_type', '=', 'product_type.id');
        $query2->where('store_product.id', $product_id);
        //echo $query2->toSql();die;
        return $query2->first();
    }

    /*public static function getStoreProducts($product_ids){
        $query2 = self::select('store_product.id','store_product.product_id','store_product.title','products.product_sku','store_product.created_at','product_type.name as ProductType')->with('Product');
        $query2->leftJoin('products','products.product_id', '=', 'store_product.product_id');
        $query2->leftJoin('product_type','products.product_type', '=', 'product_type.id');
        $query2->whereIn('store_product.product_id', $product_ids);
        return $query2->cursor();
    }*/

    public function StoreTypeCategory()
    {
        return $this->belongsTo(StoreTypeCategory::class, 'store_type_category_id');
    }

    public function product()
    {
        return $this->belongsTo(Catalog::class, 'product_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function features()
    {
        return $this->hasMany(ProductKeyFeatures::class, 'product_id','product_id');
    }

    public function productImages()
    {
        return $this->hasMany(ProductImages::class, 'product_id','product_id');
    }

}
