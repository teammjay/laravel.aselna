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
        Schema::table('device', function (Blueprint $table) {
            $table->dropForeign(['device_type_id']);
            $table->foreign('device_type_id')->references('id')->on('device_types')->nullOnDelete();

            $table->dropForeign(['device_series_id']);
            $table->foreign('device_series_id')->references('id')->on('device_series')->nullOnDelete();

            $table->dropForeign(['device_sub_type_id']);
            $table->foreign('device_sub_type_id')->references('id')->on('device_sub_types')->nullOnDelete();

            $table->dropForeign(['rating_id']);
            $table->foreign('rating_id')->references('id')->on('device_ratings')->nullOnDelete();

            $table->dropForeign(['screen_id']);
            $table->foreign('screen_id')->references('id')->on('device_screens')->nullOnDelete();

            $table->dropForeign(['brand_id']);
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();

        });

        Schema::table('order', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_condition_id']);
            $table->foreign('product_condition_id')->references('id')->on('product_condition')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
