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
        Schema::create('shipping_solution_carrier_package', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('solution_code')->nullable();
            $table->uuid('shipping_solution_id');
//            $table->foreign('shipping_solution_id')->references('id')->on('shipping_solution')->onDelete('cascade');
            $table->uuid('carrier_package_id');
            $table->foreign('carrier_package_id')->references('id')->on('shipping_carrier_package')->onDelete('cascade');
            $table->string('label_size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_solution_carrier_package');
    }
};
