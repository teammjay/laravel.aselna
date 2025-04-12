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
        Schema::create('manufacturing_order_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
            $table->uuid('order_item_id');
            $table->foreign('order_item_id')->references('id')->on('order_item')->onDelete('cascade');
            $table->uuid('manufacturing_order_id');
            $table->foreign('manufacturing_order_id')->references('id')->on('manufacturing_order')->onDelete('cascade');
            $table->uuid('product_id');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->uuid('image_id')->nullable();
            $table->foreign('image_id')->references('id')->on('sys_file')->onDelete('cascade');
            $table->uuid('thumbnail_id')->nullable();
            $table->foreign('thumbnail_id')->references('id')->on('sys_file')->onDelete('cascade');
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
        Schema::dropIfExists('manufacturing_order_item');
    }
};
