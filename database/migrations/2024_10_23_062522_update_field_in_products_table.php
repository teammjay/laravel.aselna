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
            $table->integer('product_type')->change();
            $table->string('product_sku')->unique()->change();
            $table->text('product_description')->nullable()->change();
            $table->string('product_image_url')->nullable()->change();
            $table->decimal('product_weight',10,2)->nullable()->change();
            $table->string('product_dimensions')->nullable()->change();
            $table->decimal('product_package_weight',10,2)->nullable()->change();
            $table->string('product_package_dimensions')->nullable()->change();
            $table->string('product_tags')->nullable()->change();
            $table->dropColumn('product_date_added');
            $table->dropColumn('product_date_updated');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

        });
    }
};
