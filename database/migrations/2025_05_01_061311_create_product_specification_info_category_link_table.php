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
        Schema::create('product_specification_info_category_link', function (Blueprint $table) {
            $table->uuid('specification_info_id');
            $table->foreign('specification_info_id')->references('id')->on('product_specification_info')->onDelete('cascade');
            $table->uuid('product_type_id');
            $table->foreign('product_type_id')->references('id')->on('product_type')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specification_info_category_link');
    }
};
