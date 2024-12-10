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
            // remove forein also
            $table->dropForeign(['store_category_id']);
            $table->dropColumn('store_category_id');
            $table->uuid('store_type_category_id')->nullable()->after('store_id');
            //$table->foreign('store_type_category_id')->references('id')->on('store_type_category')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
