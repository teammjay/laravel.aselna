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
        Schema::create('store_product', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->foreign('product_id')->references('product_id')->on('products')
                ->onDelete('cascade');
            $table->uuid('store_id');
            $table->foreign('store_id')->references('store_id')->on('store')
                ->onDelete('cascade');
            $table->uuid('store_category_id');
            $table->foreign('store_category_id')->references('id')->on('store_cateogry')
                ->onDelete('cascade');
            $table->string('title');
            $table->boolean('active');
            $table->string('errors');
            $table->float('profit_rate');
            $table->uuid('client');
            $table->float('price');
            $table->float('msrp');
            $table->text('description');
            $table->uuid('channel_category_id');
            $table->integer('sales_count');
            $table->date('last_sale_date');
            $table->boolean('include_carriers');
            $table->boolean('brand_combo');
            $table->boolean('carrier_combo');
            $table->uuid('manufacturer_id');
            $table->foreign('manufacturer_id')->references('id')->on('manufacturer')
                ->onDelete('cascade');
            $table->string('created_by');
            $table->string('updated_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_product');
    }
};
