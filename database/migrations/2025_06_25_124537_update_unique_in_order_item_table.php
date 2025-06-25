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
        Schema::table('order_item', function (Blueprint $table) {
            //create a unique constraint on the order_item table of order_id and sku columns
            $table->unique(['order_id', 'sku'], 'unique_order_item_order_id_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_item', function (Blueprint $table) {
            //drop the unique constraint on the order_item table of order_id and sku columns
            $table->dropUnique('unique_order_item_order_id_sku');
        });
    }
};
