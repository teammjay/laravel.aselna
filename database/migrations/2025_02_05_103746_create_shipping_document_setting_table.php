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
        Schema::create('shipping_document_setting', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client');
            $table->foreign('client')->references('id')->on('client')->onDelete('cascade');
            $table->boolean('enabled');
            $table->integer('document_type');
            $table->uuid('printer_id');
            $table->foreign('printer_id')->references('id')->on('printer')->onDelete('cascade');
            $table->uuid('shipping_from_location_id');
            $table->foreign('shipping_from_location_id')->references('id')->on('logistic_center')->onDelete('cascade');
            $table->uuid('page_size_id');
            $table->foreign('page_size_id')->references('id')->on('document_page_size')->onDelete('cascade');
            $table->boolean('two_per_page');
            $table->integer('pdf_action')->nullable();
            $table->integer('rotate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_document_setting');
    }
};
