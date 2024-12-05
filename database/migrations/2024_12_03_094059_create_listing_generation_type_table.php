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
        Schema::create('listing_generation_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code');
            $table->boolean('singleton');
            $table->boolean('allow_duplicates');
            $table->integer('popular_device')->default(0);
            $table->integer('include_carrier')->default(0);
            $table->integer('include_device')->default(0);
            $table->integer('carrier_combo')->default(0);
            $table->integer('brand_combo')->default(0);
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
        Schema::dropIfExists('listing_generation_type');
    }
};
