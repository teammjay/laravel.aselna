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
        Schema::create('shipping_package_shipping_carrier_package_link', function (Blueprint $table) {
            $table->uuid('shipping_package_id');
            $table->foreign('shipping_package_id','spsc_link_sp_fk')->references('id')->on('shipping_package')->onDelete('cascade');
            $table->uuid('shipping_carrier_package_id');
            $table->foreign('shipping_carrier_package_id','spsc_link_scp_fk')->references('id')->on('shipping_carrier_package')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_package_shipping_carrier_package_link');
    }
};
