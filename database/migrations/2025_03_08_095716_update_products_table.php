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
            $table->uuid('product_material_id')->nullable();
            $table->foreign('product_material_id')->references('id')->on('product_material');
            $table->uuid('product_size_id')->nullable();
            $table->foreign('product_size_id')->references('id')->on('product_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_material_id']);
            $table->dropColumn('product_material_id');
            $table->dropForeign(['product_size_id']);
            $table->dropColumn('product_size_id');
        });
    }
};
