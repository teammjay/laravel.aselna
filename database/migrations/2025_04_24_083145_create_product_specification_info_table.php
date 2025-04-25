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
        Schema::create('product_specification_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('field_name');
            $table->string('field_local_name');
            $table->boolean('required')->default(0);
            $table->uuid('store_type_id')->nullable();
            $table->foreign('store_type_id')->references('id')->on('store_type')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specification_info');
    }
};
