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
        Schema::table('shipping_document_setting', function (Blueprint $table) {
            $table->uuid('client')->nullable()->change();
            $table->dropForeign(['client']);
            $table->boolean('enabled')->nullable()->change();
            $table->integer('document_type')->nullable()->change();
            $table->uuid('printer_id') ->nullable()->change();
            $table->uuid('shipping_from_location_id')->nullable()->change();
            // Remove foreign key constraint before changing the column
            $table->dropForeign(['shipping_from_location_id']);
            $table->uuid('page_size_id')->nullable()->change();
            $table->boolean('two_per_page')->nullable()->change();
            $table->integer('pdf_action')->nullable()->change();
            $table->integer('rotate')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_document_setting', function (Blueprint $table) {
            $table->uuid('client')->change();
            $table->foreign('client')->references('id')->on('client')->onDelete('cascade');
            $table->boolean('enabled')->change();
            $table->integer('document_type')->change();
            $table->uuid('printer_id')->change();
            $table->uuid('shipping_from_location_id')->change();
            $table->foreign('shipping_from_location_id')->references('id')->on('logistic_center')->onDelete('cascade');
            $table->uuid('page_size_id')->change();
            $table->boolean('two_per_page')->change();
            $table->integer('pdf_action')->change();
            $table->integer('rotate')->change();
        });
    }
};
