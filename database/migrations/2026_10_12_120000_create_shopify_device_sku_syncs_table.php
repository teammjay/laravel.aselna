<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // What SyncShopifyDeviceSkuMetafieldsJob last pushed to the global.device_skus metafield of a
        // Shopify product: the sha1 of the {device_id: sku} JSON. A product is only pushed again when
        // the hash changes.
        Schema::create('shopify_device_sku_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('store_id', 36);
            $table->string('listing_item_id', 36);
            $table->char('payload_hash', 40);
            $table->unsignedInteger('device_count')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'listing_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_device_sku_syncs');
    }
};
