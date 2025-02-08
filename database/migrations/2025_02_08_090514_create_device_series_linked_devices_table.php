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
        Schema::create('device_series_linked_devices', function (Blueprint $table) {
            $table->uuid('device_id');
            $table->uuid('device_series_id');
            $table->foreign('device_id')->references('id')->on('device')->onDelete('cascade');
            $table->foreign('device_series_id')->references('id')->on('device_series')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_series_linked_devices');
    }
};
