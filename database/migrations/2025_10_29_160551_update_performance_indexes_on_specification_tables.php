<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_specification', function (Blueprint $table) {
            $table->index(['product_id', 'store_type_id', 'name'],'prod_store_name_idx');
            $table->index(['product_id', 'store_type_id'], 'prod_store_idx');
        });

        Schema::table('product_specification_info', function (Blueprint $table) {
            $table->index(['store_type_id', 'field_name'], 'store_field_idx');
        });

        Schema::table('product_specification_values', function (Blueprint $table) {
            $table->index(['product_specification_id', 'store_type_id', 'category'],'spec_store_cat_idx');
            $table->index(['product_specification_id', 'store_type_id'],'spec_store_idx');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_specification', function (Blueprint $table) {
            $table->dropIndex('prod_store_name_idx');
            $table->dropIndex('prod_store_idx');
        });

        Schema::table('product_specification_info', function (Blueprint $table) {
            $table->dropIndex('store_field_idx');
        });

        Schema::table('product_specification_values', function (Blueprint $table) {
            $table->dropIndex('spec_store_cat_idx');
            $table->dropIndex('spec_store_idx');
        });
    }
};
