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
        Schema::create('product_specification_unique_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('field_name');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('product_specification_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_specification_id');
            $table->string('field_value');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->foreign('product_specification_id')->references('id')->on('product_specification_unique_info')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specification_unique_info');
        Schema::dropIfExists('product_specification_values');
    }
};
