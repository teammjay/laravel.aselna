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
        Schema::table('listing_item_product_images', function (Blueprint $table) {
            $table->uuid('product_image_id')->nullable()->change();
            $table->boolean('main_image')->nullable();
            $table->uuid('alt_product_image_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_item_product_images', function (Blueprint $table) {
            $table->dropColumn('main_image');
            $table->dropColumn('alt_product_image_id');
        });
    }
};
