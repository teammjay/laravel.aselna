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
        Schema::create('marketplace_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id');
            $table->foreign('store_id')->references('id')->on('store')->onDelete('cascade');

            $table->string('name')->nullable();

            $table->uuid('category_id')->nullable();

            $table->string('marketplace_category_id')->nullable();
            $table->string('marketplace_parent_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_category_mappings');
    }
};
