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
        Schema::create('store_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->integer('max_title_length')->default(0);
            $table->integer('max_variation_name_length')->default(0);
            $table->integer('max_listing_variants')->default(0)->nullable();
            $table->integer('max_random_title_length')->default(0)->nullable();
            $table->boolean('acknowledge_order')->default(0)->nullable();
            $table->integer('min_image_width')->default(0)->nullable();
            $table->integer('min_image_height')->default(0)->nullable();
            $table->integer('order_shipment_control_backward_days')->default(0)->nullable();
            $table->boolean('create_image_per_listing')->default(0)->nullable();
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
        Schema::dropIfExists('store_type');
    }
};
