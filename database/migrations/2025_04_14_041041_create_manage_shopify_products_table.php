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
        Schema::create('manage_marketplace_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('product_code');
            $table->uuid('sku')->nullable();
            $table->uuid('store_id');
            $table->string('device_id')->nullable();
            $table->string('marketplace_product_id')->nullable();
            $table->string('marketplace_variant_id')->nullable();
            $table->enum('upload_status', ['pending', 'success', 'failed'])->default('pending');
            $table->integer('upload_attempts')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manage_marketplace_products');
    }
};
