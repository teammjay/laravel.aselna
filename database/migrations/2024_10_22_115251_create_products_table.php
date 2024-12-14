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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('product_id')->primary();
            $table->string('product_title')->nullable();
            $table->text('product_description')->nullable();
            $table->string('product_type')->nullable();
            $table->integer('product_manufacturer')->nullable();
            $table->string('product_condition')->nullable();
            $table->decimal('product_price', 10,2)->nullable();
            $table->string('product_sku')->nullable();
            $table->integer('product_stock_quantity')->nullable();
            $table->string('product_image_url')->nullable();
            $table->enum('product_status',['active','discontinued','out_of_stock']);
            $table->decimal('product_weight',10,2)->nullable();
            $table->string('product_dimensions')->nullable();
            $table->decimal('product_package_weight',10,2)->nullable();
            $table->string('product_package_dimensions')->nullable();
            $table->string('product_tags')->nullable();
            $table->dateTime('product_date_added')->nullable();
            $table->dateTime('product_date_updated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
