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
        Schema::create('shipping_carrier_channel_detail', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipping_carrier_id');
            $table->foreign('shipping_carrier_id')->references('id')->on('shipping_carrier')->onDelete('cascade');
            $table->uuid('store_type_id');
            $table->foreign('store_type_id')->references('id')->on('store_type')->onDelete('cascade');
            $table->string('code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_carrier_channel_detail');
    }
};
