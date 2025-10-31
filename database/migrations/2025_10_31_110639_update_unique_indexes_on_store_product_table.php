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
        Schema::table('store_product',function (Blueprint $table){
            $table->unique(['product_id','store_id'],'prod_store_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_product',function (Blueprint $table){
            $table->dropUnique('prod_store_unique_idx');
        });
    }
};
