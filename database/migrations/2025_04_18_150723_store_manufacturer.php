<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_manufacturer', function (Blueprint $table) {
            $table->uuid('store_id');
            $table->foreign('store_id')->references('id')->on('store')->onDelete('cascade');
            $table->uuid('manufacturer_id');
            $table->foreign('manufacturer_id')->references('id')->on('manufacturer')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_manufacturer');
    }
};
