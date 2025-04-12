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
        Schema::create('product_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->uuid('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('product_type')->onDelete('cascade');
            $table->uuid('image_id')->nullable();
            $table->uuid('thumbnail_id')->nullable();
            $table->string('ref_key')->nullable();
            $table->string('image_file')->nullable();
            $table->integer('sort_order')->nullable();
            $table->integer('client_category')->nullable();
            $table->string('singular_name')->nullable();
            $table->string('simple_name')->nullable();
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
        Schema::dropIfExists('product_type');
    }
};
