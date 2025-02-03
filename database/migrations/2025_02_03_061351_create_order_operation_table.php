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
        Schema::create('order_operation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tracking_number')->nullable();
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
            $table->timestamp('ship_date');
            $table->double('cost')->nullable();
            $table->boolean('synchronized_with_store')->nullable();
            $table->double('insurance_value');
            $table->uuid('shipment_batch_id')->nullable();
            $table->foreign('shipment_batch_id')->references('id')->on('shipment_batch')->onDelete('cascade');
            $table->uuid('to_address_id')->nullable();
            $table->foreign('to_address_id')->references('id')->on('postal_address')->onDelete('cascade');
            $table->integer('sync_status')->nullable();
            $table->string('sync_message')->nullable();
            $table->integer('status');
            $table->string('status_message')->nullable();
            $table->double('own_packaging_dimensions_length')->nullable();
            $table->double('own_packaging_dimensions_width')->nullable();
            $table->double('own_packaging_dimensions_height')->nullable();
            $table->integer('own_packaging_dimensions_length_unit')->nullable();
            $table->integer('signature_confirmation');
            $table->uuid('shipping_package_id')->nullable();
            $table->foreign('shipping_package_id')->references('id')->on('shipping_package')->onDelete('cascade');
            $table->double('weight_value')->nullable();
            $table->integer('weight_weight_unit')->nullable();
            $table->uuid('ship_from_id');
            $table->foreign('ship_from_id')->references('id')->on('logistic_center')->onDelete('cascade');
            $table->uuid('store_id');
            $table->foreign('store_id')->references('id')->on('store')->onDelete('cascade');
            $table->integer('operation_type');
            $table->date('expected_delivery_date')->nullable();
            $table->uuid('order_synchronization_id')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('reference_id2')->nullable();
            $table->date('completed_date')->nullable();
            $table->boolean('refund_without_return')->nullable();
            $table->uuid('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
            $table->integer('label_creation_type');
            $table->string('rma_number')->nullable();
            $table->uuid('return_charge_reject_reason_id')->nullable();
            $table->foreign('return_charge_reject_reason_id')->references('id')->on('order_return_charge_reject_reason')->onDelete('cascade');
            $table->boolean('accept_return_charge')->nullable();
            $table->uuid('return_operation_id')->nullable();
            $table->foreign('return_operation_id')->references('id')->on('return_operation')->onDelete('cascade');
            $table->uuid('shipping_carrier_id')->nullable();
            $table->foreign('shipping_carrier_id')->references('id')->on('shipping_carrier')->onDelete('cascade');
            $table->uuid('shipping_carrier_service_id')->nullable();
            $table->foreign('shipping_carrier_service_id')->references('id')->on('shipping_carrier_service')->onDelete('cascade');
            $table->uuid('hd_ticket_id')->nullable();
            $table->foreign('hd_ticket_id')->references('id')->on('hd_ticket')->onDelete('cascade');
            $table->boolean('buyer_initiated');
            $table->string('shipping_transaction_id')->nullable();
            $table->uuid('shipping_account_transaction_id')->nullable();
            $table->foreign('shipping_account_transaction_id')->references('id')->on('shipping_account_transaction')->onDelete('cascade');
            $table->boolean('print_to_file');
            $table->integer('previous_operation_type')->nullable();
            $table->string('available_actions')->nullable();
            $table->boolean('seller_provides_return_label');
            $table->string('products_rubber_stamp')->nullable();
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
        Schema::dropIfExists('order_operation');
    }
};
