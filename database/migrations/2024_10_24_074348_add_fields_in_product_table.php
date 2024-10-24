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
        Schema::table('product', function (Blueprint $table) {

            Schema::table('products', function (Blueprint $table) {

                $table->decimal('product_wholesale_price',10,2)->nullable()->after('product_price');
                $table->decimal('product_marketplace_price',10,2)->nullable()->after('product_wholesale_price');
                $table->decimal('product_mrsp',10,2)->nullable()->after('product_marketplace_price');;
                $table->integer('dimensions_width')->nullable()->after('product_status');
                $table->integer('dimensions_height')->nullable()->after('dimensions_width');
                $table->integer('dimensions_length')->nullable()->after('dimensions_height');
                $table->string('product_weight_unit')->nullable()->after('product_weight');
                $table->string('package_weight_unit')->nullable()->after('product_package_weight');
                $table->integer('package_dimensions_width')->nullable()->after('package_weight_unit');
                $table->integer('package_dimensions_height')->nullable()->after('package_dimensions_width');
                $table->integer('package_dimensions_length')->nullable()->after('package_dimensions_height');
                $table->string('target_aud')->nullable()->after('product_tags');
                $table->string('product_color')->nullable()->after('target_aud');
                $table->string('product_qty')->nullable()->after('product_color');
                $table->string('product_reorder_qty')->nullable()->after('product_qty');
                $table->string('product_pick_location')->nullable()->after('product_reorder_qty');
                $table->dropColumn('product_dimensions');
                $table->dropColumn('product_package_dimensions');
                $table->dropColumn('product_stock_quantity');

            });

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            //
        });
    }
};
