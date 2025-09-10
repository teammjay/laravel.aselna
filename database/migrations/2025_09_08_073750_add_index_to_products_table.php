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
        Schema::table('products', function (Blueprint $table) {
            $table->index('product_type');
            $table->index('product_price');
            $table->index('product_sku');
            $table->index('product_image_url');
            $table->index('product_status');
            $table->index('product_weight');
            $table->index('product_weight_unit');
            $table->index('product_color');
            $table->index('product_code');
            $table->index('created_at');
            $table->index('shipping_package_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('product_type');
            $table->dropIndex('product_price');
            $table->dropIndex('product_sku');
            $table->dropIndex('product_image_url');
            $table->dropIndex('product_status');
            $table->dropIndex('product_weight');
            $table->dropIndex('product_weight_unit');
            $table->dropIndex('product_color');
            $table->dropIndex('product_code');
            $table->dropIndex('created_at');
            $table->dropIndex('shipping_package_id');
        });
    }
};
