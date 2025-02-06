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
        Schema::create('shipping_setting_carrier_account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipping_carrier_id');
            $table->foreign('shipping_carrier_id')->references('id')->on('shipping_carrier')->onDelete('cascade');
            $table->uuid('shipping_account_id');
            $table->foreign('shipping_account_id')->references('id')->on('shipping_account')->onDelete('cascade');
            $table->uuid('shipping_setting_id');
            $table->foreign('shipping_setting_id')->references('id')->on('shipping_setting')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_setting_carrier_account');
    }
};
