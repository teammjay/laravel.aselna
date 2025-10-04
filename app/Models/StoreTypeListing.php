<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StoreTypeListing extends Model
{
    // Set a custom table name
    protected $table = 'store_type_listing';

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


    protected $fillable = ['id', 'seller_id', 'sku', 'product_name', 'description', 'product_category', 'price', 'publish_status', 'status_change_reason', 'inventory_count', 'wpid', 'item_id', 'upc', 'shelf_name', 'primary_catalog_path', 'item_page_url', 'review_count', 'avarage_rating', 'primary_image_url', 'store_id', 'ship_price', 'buy_box_price', 'buy_box_shipping_price', 'seller_buy_box_winner', 'buybox_winner_id', 'has_offers', 'status', 'quality_field_name', 'quality_alert_type', 'quality_current_value', 'quality_last_updated', 'quality_alert_name', 'quality_explanation', 'item_condition', 'removed_externally', 'report_feed_id', 'merged_with_another_listing', 'previous_item_id', 'created_by', 'updated_by', 'deleted_by', 'deleted_at', 'created_at', 'updated_at'];

    public function product()
    {
        return $this->belongsTo(Catalog::class, 'product_id')->where(function ($q) {
            $q->where('product_status', 'active');
        });
    }

    /*public static function generateUniqueAlphabeticSku($product_code = '', $length = 6)
    {
        Log::info("Inside Generating SKU for product code: $product_code");
        do {
            if ($product_code != '') {
                $sku = strtoupper($product_code . '-' . Str::random($length));
            } else {
                $sku = strtoupper(Str::random($length));
            }
            Log::info("Generated SKU: $sku");

            $exists = ListingItem::where('sku', $sku)->exists() ||
                ListingItemDetail::where('sku', $sku)->exists();

        } while ($exists);  // Check if SKU exists in the database
        Log::info("Returning SKU: $sku");
        return $sku;
    }*/

    public static function generateUniqueAlphabeticSku($product_code = '', $length = 6)
    {
        $attempts = 0;
        $maxAttempts = 10;
        Log::info("Inside Generating SKU for product code: $product_code");
        do {
            $attempts++;

            if ($product_code != '') {
                $sku = strtoupper($product_code . '-' . Str::random($length));
            } else {
                $sku = strtoupper(Str::random($length));
            }
            Log::info("Generated SKU: $sku");

            $exists = ListingItem::where('sku', $sku)->exists() ||
                ListingItemDetail::where('sku', $sku)->exists();

            if (!$exists) {
                Log::info("Returning SKU: $sku");
                return $sku;
            }

        } while ($attempts < $maxAttempts);

        if ($product_code != '') {
            $fallbacksku = strtoupper($product_code . '-' . Str::random($length));
        } else {
            $fallbacksku = strtoupper(Str::random($length));
        }

        Log::info("Fallback SKU: $fallbacksku");
        return $fallbacksku;
    }

}
