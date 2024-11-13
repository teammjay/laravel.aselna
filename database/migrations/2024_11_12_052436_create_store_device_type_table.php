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
        Schema::create('store_device_type', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id');
            $table->foreign('store_id')->references('id')->on('store')
                ->onDelete('cascade');
            $table->integer('device_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_device_type');
    }
};
