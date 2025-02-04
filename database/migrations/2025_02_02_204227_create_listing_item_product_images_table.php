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
        Schema::create('listing_item_product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('listing_item_id');
            $table->uuid('product_image_id');
            $table->uuid('image_file_system_id')->nullable();
            $table->string('path', 1024);
            $table->string('reference_id', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_item_product_images');
    }
};
