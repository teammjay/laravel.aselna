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
            $table->string('product_title')->nullable()->change();
            $table->string('product_type')->nullable()->change();
            $table->integer('product_manufacturer')->nullable()->change();
            $table->string('product_condition')->nullable()->change();
            $table->decimal('product_price', 10,2)->nullable()->change();
            $table->string('product_sku')->nullable()->change();
            $table->enum('product_status',['active','discontinued','out_of_stock','draft'])->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('product_status',['active','discontinued','out_of_stock','draft'])->change();
        });
    }
};
