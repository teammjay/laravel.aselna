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
            $table->uuid('store_id')->nullable();
            $table->foreign('store_id')->references('id')->on('store')
                ->onDelete('cascade');
            $table->uuid('store_category_id')->nullable();
            $table->foreign('store_category_id')->references('id')->on('store_category')
                ->onDelete('cascade');
            $table->text('title','')->nullable();
            $table->boolean('active')->nullable();
            $table->string('errors')->nullable();
            $table->float('profit_rate')->nullable();
            $table->uuid('client')->nullable();
            $table->float('price')->nullable();
            $table->float('msrp')->nullable();
            $table->text('description')->nullable();
            $table->uuid('channel_category_id')->nullable();
            $table->integer('sales_count')->nullable();
            $table->date('last_sale_date')->nullable();
            $table->boolean('include_carriers')->nullable();
            $table->boolean('brand_combo')->nullable();
            $table->boolean('carrier_combo')->nullable();
            $table->uuid('manufacturer_id')->nullable();
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
