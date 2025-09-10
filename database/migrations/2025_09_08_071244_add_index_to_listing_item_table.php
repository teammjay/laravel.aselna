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
        Schema::table('listing_item', function (Blueprint $table) {
            $table->index('sku');
            $table->index('price');
            $table->index('store_sku');
            $table->index('device_id');
            $table->index('brand_id');
            $table->index('quantity');
            $table->index('last_sale_date');
            $table->index('sales_count');
            $table->index('upc');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_item', function (Blueprint $table) {
            $table->dropIndex('sku');
            $table->dropIndex('price');
            $table->dropIndex('store_sku');
            $table->dropIndex('device_id');
            $table->dropIndex('brand_id');
            $table->dropIndex('quantity');
            $table->dropIndex('last_sale_date');
            $table->dropIndex('sales_count');
            $table->dropIndex('upc');
            $table->dropIndex('status');
            $table->dropIndex('created_at');
        });
    }
};
