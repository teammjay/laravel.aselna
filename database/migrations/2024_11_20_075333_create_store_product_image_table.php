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
        Schema::create('store_product_image', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_image_id');
            $table->uuid('image_file_system_id')->nullable();
            $table->string('path')->nullable();
            $table->uuid('store_product_id');
            $table->foreign('store_product_id')->references('id')->on('store_product')
                ->onDelete('cascade');
            $table->string('reference_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_product_image');
    }
};
