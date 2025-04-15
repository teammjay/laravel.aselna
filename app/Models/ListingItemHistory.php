<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListingItemHistory extends Model
{
    // Set a custom table name
    protected $table = 'listing_item_history';

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


    protected $fillable = ['id', 'store_type_listing_id', 'title', 'description', 'sku', 'store_sku', 'variant_id', 'store_url', 'parent_id', 'price', 'sync_status', 'processing_days', 'quantity', 'creation_order', 'creation_retry_count', 'linked_item_id', 'carrier_id', 'device_id', 'store_product_id', 'generation_type_id', 'brand_id', 'device_carrier_id', 'color_id', 'size_id', 'status', 'client', 'upc', 'brand_combo', 'carrier_combo', 'submit_count', 'replaced_id', 'sync_message', 'mpn', 'manufacturer_id', 'sales_count', 'last_sale_date', 'image_version', 'fulfillment_type', 'overwrite_price', 'overwrite_msrp', 'overwrite_compare_at_price', 'overwrite_title', 'overwrite_description', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    public static function createBackup($listing_item)
    {
        // create a new entry in the history table
        $history = new ListingItemHistory();
        $history->title = $listing_item->title;
        $history->description = $listing_item->description;
        $history->sku = $listing_item->sku;
        $history->store_sku = $listing_item->store_sku;
        $history->variant_id = $listing_item->variant_id;
        $history->store_url = $listing_item->store_url;
        $history->parent_id = $listing_item->parent_id;
        $history->price = $listing_item->price;
        $history->quantity = $listing_item->quantity;
        $history->creation_order = $listing_item->creation_order;
        $history->creation_retry_count = $listing_item->creation_retry_count;
        $history->linked_item_id = $listing_item->linked_item_id;
        $history->carrier_id = $listing_item->carrier_id;
        $history->device_id = $listing_item->device_id;
        $history->store_product_id = $listing_item->store_product_id;
        $history->generation_type_id = $listing_item->generation_type_id;
        $history->brand_id = $listing_item->brand_id;
        $history->device_carrier_id = $listing_item->device_carrier_id;
        $history->color_id = $listing_item->color_id;
        $history->size_id = $listing_item->size_id;
        $history->status = $listing_item->status;
        $history->client = $listing_item->client;
        $history->upc = $listing_item->upc;
        $history->brand_combo = $listing_item->brand_combo;
        $history->carrier_combo = $listing_item->carrier_combo;
        $history->submit_count = $listing_item->submit_count;
        $history->replaced_id = $listing_item->replaced_id;
        $history->sync_message = $listing_item->sync_message;
        $history->mpn = $listing_item->mpn;
        $history->manufacturer_id = $listing_item->manufacturer_id;
        $history->sales_count = $listing_item->sales_count;
        $history->last_sale_date = $listing_item->last_sale_date;
        $history->image_version = $listing_item->image_version;
        $history->fulfillment_type = $listing_item->fulfillment_type;
        $history->overwrite_price = $listing_item->overwrite_price;
        $history->overwrite_msrp = $listing_item->overwrite_msrp;
        $history->overwrite_compare_at_price = $listing_item->overwrite_compare_at_price;
        $history->overwrite_title = $listing_item->overwrite_title;
        $history->overwrite_description = $listing_item->overwrite_description;
        $history->created_by = $listing_item->created_by;
        $history->updated_by = $listing_item->updated_by;
        $history->item_id = $listing_item->id;
        $history->save();

    }

}
