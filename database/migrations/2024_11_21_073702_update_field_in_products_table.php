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
            $table->dropColumn('product_manufacturer');
            $table->uuid('manufacturer_id')->nullable()->after('product_type');
            $table->foreign('manufacturer_id')->references('id')->on('manufacturer')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // remove the foreign key first
            $table->dropForeign(['manufacturer_id']);
//            $table->uuid('product_manufacturer')->after('product_type');
        });
    }
};
