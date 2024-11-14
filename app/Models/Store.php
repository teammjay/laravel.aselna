<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Store extends Model
{
    // Set a custom table name
    protected $table = 'store';

    // Custom primary key
    protected $primaryKey = 'id';

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


    protected $fillable = ['id','name','store_status','api_key','password','access_key','secret_key','app_name','app_version','service_url','marketplace_name','listing_type','brand_name','sku_generation_type','profit_rate','default_brand_id','image_base_path','store_type_id','short_code','listing_template_id','upc_usage_type','upc_default_company_prefix','working_hours','test_store','fulfilment_node_id','default_listing_quantity','ad_account','linked_store_id','order_cutoff_time','logo_code','standard_shipping_name','logo_code_base64','wholesale','auto_generate_listings','max_listing_inventory_quantity','free_shipping_amount','default_shipping_amount','store_url','random_title_keyword_level','download_listings','download_buy_box','download_reviews','product_alt_image_number','productaltimagenumber','generate_device_details','label_order_prefix','alter_image_files','resize_images','apply_watermark','watermark_image_id','watermark_transparency','images_location','app_id','dev_id','certificate','authorized','seller_id','first_name','last_name','company','address_1','address_2','address_3','city','country','district','state','postal_code','country_code','phone','email'];
}
