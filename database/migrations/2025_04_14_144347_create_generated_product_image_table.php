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
        Schema::create('generated_product_image', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('product_id');
            $table->uuid('product_image_id')->nullable();
            $table->uuid('image_file_system_id')->nullable();
            $table->string('path',1024);
            $table->string('reference_id')->nullable();
            $table->tinyInteger('main_image')->nullable();
            $table->uuid('alt_product_image_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_product_image');
    }
};
