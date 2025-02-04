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
        Schema::create('shipping_carrier_package', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipping_carrier_id')->nullable();
            $table->foreign('shipping_carrier_id')->references('id')->on('shipping_carrier')->onDelete('cascade');
            $table->string('name');
            $table->float('dimensions_length')->nullable();
            $table->float('dimensions_width')->nullable();
            $table->float('dimensions_height')->nullable();
            $table->integer('dimensions_length_unit')->nullable();
            $table->float('max_weight_value')->nullable();
            $table->integer('max_weight_weight_unit')->nullable();
            $table->boolean('non_machinable');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_carrier_package');
    }
};
