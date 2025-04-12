<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('popular_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('device_type_id')->nullable();
            $table->uuid('device_id');
            $table->timestamps();

            $table->foreign('device_type_id')->references('id')->on('device_types');
            $table->foreign('device_id')->references('id')->on('device');
        });
    }

    public function down()
    {
        Schema::dropIfExists('popular_devices');
    }
};