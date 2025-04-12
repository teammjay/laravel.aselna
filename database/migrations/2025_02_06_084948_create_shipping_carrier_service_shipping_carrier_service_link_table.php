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
        Schema::create('shipping_carrier_service_shipping_carrier_service_link', function (Blueprint $table) {
            $table->uuid('shipping_carrier_service_1_id');
            $table->foreign('shipping_carrier_service_1_id','scs_scs_link_scs1_fk1')->references('id')->on('shipping_carrier_service')->onDelete('cascade');
            $table->uuid('shipping_carrier_service_2_id');
            $table->foreign('shipping_carrier_service_2_id','scs_scs_link_scs1_fk2')->references('id')->on('shipping_carrier_service')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_carrier_service_shipping_carrier_service_link');
    }
};
