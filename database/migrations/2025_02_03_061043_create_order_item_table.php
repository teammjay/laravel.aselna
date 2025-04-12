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
        Schema::create('order_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->nullable();
            $table->uuid('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
            $table->uuid('product_id')->nullable();
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
            $table->uuid('listing_item_id')->nullable();
            $table->foreign('listing_item_id')->references('id')->on('listing_item')->onDelete('cascade');
            $table->string('sku')->nullable();
            $table->string('store_sku')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('id1')->nullable();
            $table->string('id2')->nullable();
            $table->double('item_price')->nullable();
            $table->uuid('image_id')->nullable();
            $table->foreign('image_id')->references('id')->on('sys_file')->onDelete('cascade');
            $table->uuid('thumbnail_id')->nullable();
            $table->foreign('thumbnail_id')->references('id')->on('sys_file')->onDelete('cascade');
            $table->double('shipping_price')->nullable();
            $table->double('item_tax')->nullable();
            $table->double('shipping_tax')->nullable();
            $table->integer('shipped_quantity')->nullable();
            $table->integer('canceled_quantity')->nullable();
            $table->integer('refund_quantity')->nullable();
            $table->double('refund_price')->nullable();
            $table->double('refund_tax_price')->nullable();
            $table->double('refund_shipping_price')->nullable();
            $table->double('refund_shipping_tax')->nullable();
            $table->integer('acknowledged_quantity')->nullable();
            $table->integer('replaced_quantity')->nullable();
            $table->integer('return_request_quantity')->nullable();
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
        Schema::dropIfExists('order_item');
    }
};
