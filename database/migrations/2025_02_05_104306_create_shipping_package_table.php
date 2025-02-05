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
        Schema::create('shipping_package', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client');
            $table->foreign('client')->references('id')->on('client')->onDelete('cascade');
            $table->double('dimensions_length')->nullable();
            $table->double('dimensions_width')->nullable();
            $table->double('dimensions_height')->nullable();
            $table->integer('dimensions_length_unit')->nullable();
            $table->string('name');
            $table->double('weight_value')->nullable();
            $table->integer('weight_weight_unit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_package');
    }
};
