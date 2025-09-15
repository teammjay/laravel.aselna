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
        //CREATE INDEX idx_store_product_id_store ON store_product(id, store_id);
        Schema::table('store_product', function (Blueprint $table) {
            $table->index(['id', 'store_id'], 'idx_store_product_id_store');
        });

        //CREATE INDEX idx_order_item_sku ON order_item(sku);
        //CREATE INDEX idx_order_item_sku_shipped ON order_item(sku, shipped_quantity);
        //CREATE INDEX idx_order_item_sku_canceled_refund ON order_item(sku, canceled_quantity, refund_quantity);
        //CREATE INDEX idx_order_item_sku_created_at ON order_item(sku, created_at DESC);
        Schema::table('order_item', function (Blueprint $table) {
            $table->index('sku', 'idx_order_item_sku');
            $table->index(['sku', 'shipped_quantity'], 'idx_order_item_sku_shipped');
            $table->index(['sku', 'canceled_quantity', 'refund_quantity'], 'idx_order_item_sku_canceled_refund');
            $table->index(['sku', 'created_at'], 'idx_order_item_sku_created_at');
        });

        //CREATE INDEX idx_listing_item_store_product_id_status_created_at ON listing_item(store_product_id, status, created_at DESC);
        Schema::table('listing_item', function (Blueprint $table) {
            $table->index(['store_product_id', 'status', 'created_at'], 'idx_listing_item_store_product_id_status_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
