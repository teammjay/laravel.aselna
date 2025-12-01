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
        Schema::table('product_key_features', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('sort_order');
            $table->index(['product_id', 'sort_order']);
        });

        Schema::table('product_title_keyword', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('priority');
            $table->index(['product_id', 'priority']);
        });

        Schema::table('store_navigation_page', function (Blueprint $table) {
            $table->index('store_id');
            $table->index('page_type');
            $table->index('device_id');
            $table->index(['store_id', 'page_type', 'device_id']);
        });

        Schema::table('listing_item_product_images', function (Blueprint $table) {
            $table->index('listing_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_key_features', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['sort_order']);
            $table->dropIndex(['product_id', 'sort_order']);
        });

        Schema::table('product_title_keyword', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['product_id', 'priority']);
        });

        Schema::table('store_navigation_page', function (Blueprint $table) {
            $table->dropIndex(['store_id']);
            $table->dropIndex(['page_type']);
            $table->dropIndex(['device_id']);
            $table->dropIndex(['store_id', 'page_type', 'device_id']);
        });

        Schema::table('listing_item_product_images', function (Blueprint $table) {
            $table->dropIndex(['listing_item_id']);
        });
    }
};
