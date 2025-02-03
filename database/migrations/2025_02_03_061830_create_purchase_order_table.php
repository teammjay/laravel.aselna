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
        Schema::create('purchase_order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference_order_id')->nullable();
            $table->timestamp('expectede_delivery_date')->nullable();
            $table->uuid('destination_logistic_center_id');
            $table->foreign('destination_logistic_center_id')->references('id')->on('logistic_center')->onDelete('cascade');
            $table->double('shipping_cost')->nullable();
            $table->string('purchase_orderid');
            $table->uuid('supplier_id')->nullable();
            $table->foreign('supplier_id')->references('id')->on('supplier')->onDelete('cascade');
            $table->integer('status');
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->string('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order');
    }
};
