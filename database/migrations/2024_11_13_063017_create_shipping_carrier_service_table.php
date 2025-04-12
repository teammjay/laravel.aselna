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

        Schema::create('shipping_carrier_service', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('shipping_carrier_id');
            $table->foreign('shipping_carrier_id')->references('id')->on('shipping_carrier')
                ->onDelete('cascade');
            $table->float('included_insurance')->nullable();
            $table->float('default_package_dimension_length')->nullable();
            $table->float('default_package_dimension_width')->nullable();
            $table->float('default_package_dimension_height')->nullable();
            $table->integer('default_package_dimension_length_unit')->nullable();
            $table->integer('generic_service_method');
            $table->float('max_weight_value')->nullable();
            $table->integer('max_weight_weight_unit')->nullable();
            $table->integer('delivery_time')->nullable();
            $table->integer('earliest_delivery_days')->nullable();
            $table->integer('latest_delivery_days')->nullable();
            $table->integer('service_level')->nullable();
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
        Schema::dropIfExists('shipping_carrier_service');
    }
};
