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
        Schema::create('store_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_id')->nullable();
            $table->foreign('store_id')->references('id')->on('store')->onDelete('cascade');
            $table->string('session_id')->nullable();
            $table->boolean('closed');
            $table->uuid('store_type_id');
            $table->foreign('store_type_id')->references('id')->on('store_type')->onDelete('cascade');
            $table->text('signin_url')->nullable();
            $table->uuid('client');
            $table->boolean('sandbox');
            $table->integer('client_product_category');
            $table->string('seller_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_authorization');
    }
};
