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
        Schema::create('product_size', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('value_unit', 255)->nullable();
            $table->string('value', 255);
            $table->integer('position')->nullable();
            $table->string('short_code', 255)->nullable();
            $table->timestamps();
        });
//        Schema::create('product_size', function (Blueprint $table) {
//
//            $table->timestamp('create_ts')->nullable();
//            $table->string('created_by', 50)->nullable();
//            $table->timestamp('update_ts')->nullable();
//            $table->string('updated_by', 50)->nullable();
//
//            $table->uuid('client_id')->nullable();
//
//
//            $table->uuid('product_type_id')->nullable();
//            $table->uuid('channel_mapping_id')->nullable();
//
//
//            $table->foreign('client_id')->references('id')->on('oydera_client');
//            $table->foreign('product_type_id')->references('id')->on('oydera_product_type');
//            $table->foreign('channel_mapping_id')->references('id')->on('oydera_channel_mapping');
//        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_size');
    }
};
