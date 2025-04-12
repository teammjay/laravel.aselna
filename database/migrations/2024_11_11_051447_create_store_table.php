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
        Schema::create('store', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->integer('store_status')->default(0)->nullable();
            $table->string('api_key')->nullable();
            $table->string('password')->nullable();
            $table->string('access_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('app_name')->nullable();
            $table->string('app_version')->nullable();
            $table->string('service_url')->nullable();
            $table->string('marketplace_name')->nullable();
            $table->integer('listing_type')->nullable();
            $table->string('brand_name')->nullable();
            $table->integer('sku_generation_type')->nullable();
            $table->float('profit_rate')->nullable();
            $table->integer('default_brand_id')->nullable();
            $table->string('image_base_path')->nullable();
            $table->integer('store_type_id')->nullable();
            $table->string('short_code')->nullable();
            $table->integer('listing_template_id')->nullable();
            $table->integer('upc_usage_type')->nullable();
            $table->string('upc_default_company_prefix')->nullable();
            $table->string('working_hours')->nullable();
            $table->boolean('test_store')->default(0)->nullable();
            $table->string('fulfilment_node_id')->nullable();
            $table->integer('default_listing_quantity')->nullable();
            $table->boolean('ad_account')->default(0)->nullable();
            $table->integer('linked_store_id')->nullable();
            $table->time('order_cutoff_time')->nullable();
            $table->text('logo_code')->nullable();
            $table->string('standard_shipping_name')->nullable();
            $table->text('logo_code_base64')->nullable();
            $table->boolean('wholesale')->default(0)->nullable();
            $table->boolean('auto_generate_listings')->default(0)->nullable();
            $table->integer('max_listing_inventory_quantity')->nullable();
            $table->float('free_shipping_amount')->nullable();
            $table->float('default_shipping_amount')->nullable();
            $table->string('store_url')->nullable();
            $table->integer('random_title_keyword_level')->nullable();
            $table->boolean('download_listings')->default(0)->nullable();
            $table->boolean('download_buy_box')->default(0)->nullable();
            $table->boolean('download_reviews')->default(0)->nullable();
            $table->integer('product_alt_image_number')->nullable();
            $table->integer('productaltimagenumber')->nullable();
            $table->boolean('generate_device_details')->default(0)->nullable();
            $table->string('label_order_prefix')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store');
    }
};
