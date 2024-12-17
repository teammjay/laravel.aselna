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
        Schema::create('listing_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_type_listing_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('sku')->nullable();
            $table->string('store_sku')->nullable();
            $table->string('store_url')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->float('price')->nullable();
            $table->integer('sync_status')->nullable();
            $table->integer('processing_days')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('creation_order')->nullable();
            $table->integer('creation_retry_count')->nullable();
            $table->uuid('linked_item_id')->nullable();
            $table->uuid('carrier_id')->nullable();
            $table->uuid('device_id')->nullable();
            $table->uuid('store_product_id')->nullable();
            $table->uuid('generation_type_id')->nullable();
            $table->uuid('brand_id')->nullable();
            $table->uuid('device_carrier_id')->nullable();
            $table->uuid('color_id')->nullable();
            $table->uuid('size_id')->nullable();
            $table->integer('status');
            $table->uuid('client')->nullable();
            $table->string('upc')->nullable();
            $table->boolean('brand_combo')->nullable();
            $table->boolean('carrier_combo')->nullable();
            $table->integer('submit_count')->default(0);
            $table->uuid('replaced_id')->nullable();
            $table->string('sync_message')->nullable();
            $table->string('mpn')->nullable();
            $table->uuid('manufacturer_id')->nullable();
            $table->integer('sales_count')->nullable();
            $table->dateTime('last_sale_date')->nullable();
            $table->integer('image_version')->nullable();
            $table->integer('fulfillment_type')->nullable();
            $table->boolean('overwrite_price')->nullable();
            $table->boolean('overwrite_msrp')->nullable();
            $table->boolean('overwrite_compare_at_price')->nullable();
            $table->boolean('overwrite_title')->nullable();
            $table->boolean('overwrite_description')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_item');
    }
};
