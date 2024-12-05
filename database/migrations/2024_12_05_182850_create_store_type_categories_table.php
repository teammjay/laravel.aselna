<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_type_categories', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('store_type_id')->nullable();

            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('store_type_id')->references('id')->on('store_type');
            $table->uuid('old_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_type_categories');
    }
};
