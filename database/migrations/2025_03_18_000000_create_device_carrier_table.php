<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceCarrierTable extends Migration
{
    public function up()
    {
        Schema::create('device_carrier', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('created_by', 50)->nullable();
            $table->uuid('device_id');
            $table->uuid('carrier_id');
            $table->string('carrier_model_number', 255)->nullable();
            $table->integer('version')->default(0);
            $table->string('updated_by', 50)->nullable();
            $table->string('deleted_by', 50)->nullable();
            $table->timestamps();

            $table->foreign('device_id')
                ->references('id')
                ->on('device')
                ->onDelete('cascade');

            $table->foreign('carrier_id')
                ->references('id')
                ->on('carrier')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('device_carrier');
    }
}
