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
        Schema::create('manufacturing_order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
            $table->uuid('manufacturer_id');
            $table->foreign('manufacturer_id')->references('id')->on('manufacturer')->onDelete('cascade');
            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
            $table->string('notes')->nullable();
            $table->uuid('order_file_id')->nullable();
            $table->foreign('order_file_id')->references('id')->on('sys_file')->onDelete('cascade');
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
        Schema::dropIfExists('manufacturing_order');
    }
};
