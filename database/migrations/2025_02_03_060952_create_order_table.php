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
        Schema::create('order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_id')->nullable();
            $table->index('order_id');
            $table->uuid('store_id');
            $table->foreign('store_id')->references('id')->on('store')->onDelete('cascade');
            $table->timestamp('order_date')->nullable();
            $table->index('order_date');
            $table->integer('order_status');
            $table->string('buyer_first_name')->nullable();
            $table->index('buyer_first_name');
            $table->string('buyer_last_name')->nullable();
            $table->string('buyer_email')->nullable();
            $table->index('buyer_email');
            $table->double('order_total')->nullable();
            $table->uuid('shipping_address_id');
            $table->foreign('shipping_address_id')->references('id')->on('postal_address')->onDelete('cascade');
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
            $table->string('purchase_order_id')->nullable();
            $table->index('purchase_order_id');
            $table->uuid('order_synchronization_id')->nullable();
            $table->index('order_synchronization_id');
            $table->string('status_message')->nullable();
            $table->integer('number_of_sync_attempts')->nullable();
            $table->uuid('customer_id');
            $table->foreign('customer_id')->references('id')->on('customer')->onDelete('cascade');
            $table->uuid('order_operation_id')->nullable();
            $table->index('order_operation_id');
            $table->uuid('hd_ticket_id')->nullable();
            $table->foreign('hd_ticket_id')->references('id')->on('hd_ticket')->onDelete('cascade');
            $table->string('buyer_user_id')->nullable();
            $table->double('order_risk_score')->nullable();
            $table->string('order_risk_message')->nullable();
            $table->boolean('contingent')->nullable();
            $table->string('requested_shipment_service')->nullable();
            $table->string('requested_shipment_carrier')->nullable();
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
        Schema::dropIfExists('order');
    }
};
