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
        Schema::create('product_channel_detail', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('channel_id');
            $table->uuid('product_id');
            $table->foreign('product_id')->references('product_id')->on('products')
                ->onDelete('cascade');
            $table->integer('status');
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
        Schema::dropIfExists('product_channel_detail');
    }
};
