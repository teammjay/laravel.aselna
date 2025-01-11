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
        Schema::create('product_image_alternatives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('main_image_id')->nullable();
            $table->foreign('main_image_id')->references('id')->on('product_images')
                ->onDelete('cascade');
            $table->string('image')->nullable();
            $table->string('thumbnail_image')->nullable();
            $table->uuid('product_id')->nullable();
            $table->foreign('product_id')->references('product_id')->on('products')
                ->onDelete('cascade');
            $table->integer('sort_order')->nullable();
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
        Schema::dropIfExists('product_image_alternatives');
    }
};
