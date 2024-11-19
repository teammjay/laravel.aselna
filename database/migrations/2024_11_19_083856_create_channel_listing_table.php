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
        Schema::create('channel_listing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('seller_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('product_name')->nullable();
            $table->text('description')->nullable();
            $table->text('product_category')->nullable();
            $table->float('price')->nullable();
            $table->string('publish_status')->nullable();
            $table->string('status_change_reason')->nullable();
            $table->integer('inventory_count')->default(0)->nullable();
            $table->string('wpid')->nullable();
            $table->string('item_id')->nullable();
            $table->string('upc')->nullable();
            $table->string('shelf_name')->nullable();
            $table->string('primary_catalog_path')->nullable();
            $table->string('item_page_url')->nullable();
            $table->integer('review_count')->default(0)->nullable();
            $table->float('avarage_rating')->nullable();
            $table->string('primary_image_url')->nullable();
            $table->uuid('store_id');
            $table->foreign('store_id')->references('store_id')->on('store')
                ->onDelete('cascade');
            $table->float('ship_price')->nullable();
            $table->float('buy_box_price')->nullable();
            $table->float('buy_box_shipping_price')->nullable();
            $table->boolean('seller_buy_box_winner')->default(0);
            $table->uuid('buybox_winner_id');
            $table->boolean('has_offers')->default(0);
            $table->integer('status');
            $table->string('quality_field_name')->nullable();
            $table->string('quality_alert_type')->nullable();
            $table->string('quality_current_value')->nullable();
            $table->date('quality_last_updated')->nullable();
            $table->string('quality_alert_name')->nullable();
            $table->string('quality_explanation')->nullable();
            $table->text('item_condition')->nullable();
            $table->boolean('removed_externally')->default(0);
            $table->uuid('report_feed_id');
            $table->boolean('merged_with_another_listing')->default(0);
            $table->string('previous_item_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_listing');
    }
};
