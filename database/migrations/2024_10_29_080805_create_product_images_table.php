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
        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('main_image')->default(0);
            $table->uuid('image');
            $table->uuid('thumbnail_image')->nullable();
            $table->uuid('product_id');
            $table->foreign('product_id')->references('product_id')->on('products')
                ->onDelete('cascade');
            $table->integer('sort_order');
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
        Schema::dropIfExists('product_images');
    }
};
