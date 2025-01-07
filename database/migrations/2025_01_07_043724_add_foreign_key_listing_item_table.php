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
            $table->index('store_product_id');
            /*$table->foreign('store_product_id')
                ->references('id')
                ->on('store_product')
                ->onDelete('cascade');*/
            $table->index('generation_type_id');
            /*$table->foreign('generation_type_id')
                ->references('code')
                ->on('listing_generation_type')
                ->onDelete('cascade');*/
            $table->index('manufacturer_id');
            /*$table->foreign('manufacturer_id')
                ->references('id')
                ->on('manufacturer')
                ->onDelete('cascade');*/
            $table->index('store_type_listing_id');
            /*$table->foreign('store_type_listing_id')
                ->references('id')
                ->on('store_type_listing')
                ->onDelete('cascade');*/
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_item', function (Blueprint $table) {
            $table->dropIndex('store_product_id');
            $table->dropIndex('generation_type_id');
            $table->dropIndex('manufacturer_id');
            $table->dropIndex('store_type_listing_id');

            /*$table->dropForeign(['store_product_id']);
            $table->dropForeign(['generation_type_id']);
            $table->dropForeign(['manufacturer_id']);
            $table->dropForeign(['store_type_listing_id']);*/
        });
    }
};
