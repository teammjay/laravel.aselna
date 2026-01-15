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
        Schema::table('store_product', function (Blueprint $table) {
            $table->boolean('update_title')->default(false)->nullable();
            $table->boolean('update_description')->default(false)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_product',function (Blueprint $table){
            $table->dropColumn('update_title');
            $table->dropColumn('update_description');
        });
    }
};
