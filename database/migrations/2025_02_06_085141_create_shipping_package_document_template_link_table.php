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
        Schema::create('shipping_package_document_template_link', function (Blueprint $table) {
            $table->uuid('document_template_id');
//            $table->foreign('document_template_id','spdt_link_dt_fk')->references('id')->on('document_template')->onDelete('cascade');
            $table->uuid('shipping_package_id');
            $table->foreign('shipping_package_id','spdt_link_sp_fk')->references('id')->on('shipping_package')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_package_document_template_link');
    }
};
