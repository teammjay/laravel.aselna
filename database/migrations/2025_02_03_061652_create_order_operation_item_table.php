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
        Schema::create('order_operation_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_item_id')->nullable();
            $table->foreign('order_item_id')->references('id')->on('order_item')->onDelete('cascade');
            $table->uuid('order_operation_id')->nullable();
            $table->foreign('order_operation_id')->references('id')->on('order_operation')->onDelete('cascade');
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
            $table->integer('sync_status')->nullable();
            $table->string('sync_message')->nullable();
            $table->integer('quantity');
            $table->integer('creation_retry_count')->nullable();
            $table->integer('returned_quantity')->nullable();
            $table->double('item_price')->nullable();
            $table->double('shipping_price')->nullable();
            $table->double('item_tax_price')->nullable();
            $table->double('shipping_tax_price')->nullable();
            $table->uuid('reason_id')->nullable();
            $table->foreign('reason_id')->references('id')->on('order_operation_reason')->onDelete('cascade');
            $table->string('notes')->nullable();
            $table->uuid('refund_feedback_id')->nullable();
//            $table->foreign('refund_feedback_id')->references('id')->on('refund_feedback')->onDelete('cascade');
            $table->uuid('return_operation_item_id')->nullable();
//            $table->foreign('return_operation_item_id')->references('id')->on('return_operation_item')->onDelete('cascade');
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
        Schema::dropIfExists('order_operation_item');
    }
};
