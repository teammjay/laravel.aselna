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
        Schema::create('listing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_id')->nullable();
            $table->uuid('generation_type_id')->nullable();
            $table->uuid('device_id')->nullable();
            $table->integer('feed_type')->default(0);
            $table->uuid('client')->nullable();
            $table->integer('status');
            $table->boolean('chained')->nullable();
            $table->boolean('include_carriers');
            $table->boolean('brand_combo');
            $table->boolean('carrier_combo');
            $table->boolean('preserve_title_description')->nullable();
            $table->uuid('manufacturer_id')->nullable();
            $table->integer('feed_execution_delay')->nullable();
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
        Schema::dropIfExists('listing');
    }
};
