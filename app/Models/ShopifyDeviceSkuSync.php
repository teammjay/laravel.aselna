<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The last {device_id: sku} map SyncShopifyDeviceSkuMetafieldsJob pushed to a Shopify product,
 * kept as a hash so unchanged products are skipped.
 */
class ShopifyDeviceSkuSync extends Model
{
    protected $table = 'shopify_device_sku_syncs';

    protected $fillable = [
        'store_id',
        'listing_item_id',
        'payload_hash',
        'device_count',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];
}
