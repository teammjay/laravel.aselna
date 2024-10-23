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
            $table->id('product_id');
            $table->string('product_title');
            $table->text('product_description');
            $table->string('product_type');
            $table->integer('product_manufacturer');
            $table->string('product_condition');
            $table->decimal('product_price', 10,2);
            $table->string('product_sku');
            $table->integer('product_stock_quantity');
            $table->string('product_image_url');
            $table->enum('product_status',['active','discontinued','out_of_stock']);
            $table->decimal('product_weight',10,2);
            $table->string('product_dimensions');
            $table->decimal('product_package_weight',10,2);
            $table->string('product_package_dimensions');
            $table->string('product_tags');
            $table->dateTime('product_date_added');
            $table->dateTime('product_date_updated');
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
