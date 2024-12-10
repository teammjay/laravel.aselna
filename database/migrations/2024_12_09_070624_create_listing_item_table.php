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
        Schema::create('listing_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('carrier_id')->nullable();
            $table->string('model_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('carrier_name')->nullable();
            $table->uuid('device_id')->nullable();
            $table->uuid('brand_id')->nullable();
            $table->uuid('device_series_id')->nullable();
            $table->uuid('device_type_id')->nullable();
            $table->string('device_type_name')->nullable();
            $table->string('device_code')->nullable();
            $table->uuid('device_rating_id')->nullable();
            $table->integer('device_rating')->nullable();
            $table->integer('listed_count')->nullable();
            $table->integer('listed_type_count')->nullable();
            $table->integer('priority')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_item');
    }
};
