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
        Schema::create('store_type_category', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->uuid('store_type_id');
            $table->foreign('store_type_id')->references('id')->on('store_type');
            $table->uuid('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('store_type_category');
            $table->uuid('category_id')->nullable();
            $table->string('store_type_code')->nullable();
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
        Schema::dropIfExists('store_type_category');
    }
};
