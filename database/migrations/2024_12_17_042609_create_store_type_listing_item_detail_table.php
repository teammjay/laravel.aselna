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
        Schema::create('listing_item_detail', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku');
            $table->string('title');
            $table->string('upc');
            $table->string('url')->nullable();
            $table->string('store_sku')->nullable();
            $table->boolean('kicked')->default(0);
            $table->uuid('replaced_id')->nullable();
            $table->uuid('device_id')->nullable();
            $table->uuid('device_carrier_id')->nullable();
            $table->uuid('listing_item_id');
            $table->date('last_sale_date')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_item_detail');
    }
};
