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
        Schema::create('product_interface', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('interface_id');
            $table->foreign('interface_id')->references('id')->on('interface')->onDelete('cascade');
            $table->uuid('product_id');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_interface');
    }
};
