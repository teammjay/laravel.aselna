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
        Schema::create('hd_ticket', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_type_id');
            $table->foreign('ticket_type_id')->references('id')->on('hd_ticket_type')->onDelete('cascade');
            $table->string('ticket_email')->nullable();
            $table->integer('status');
//            $table->uuid('related_order_id')->nullable();
//            $table->foreign('related_order_id')->references('id')->on('order')->onDelete('cascade');
            $table->string('subject');
            $table->text('message_id')->nullable();
            $table->text('notes')->nullable();
//            $table->uuid('customer_id')->nullable();
//            $table->foreign('customer_id')->references('id')->on('customer')->onDelete('cascade');
            $table->string('reference_code')->nullable();
            $table->uuid('related_order_operation_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->uuid('store_id');
            $table->foreign('store_id')->references('id')->on('store')->onDelete('cascade');
            $table->timestamp('ticket_date');
            $table->string('ticket_code')->nullable();
            $table->boolean('message_read');
            $table->integer('replies');
            $table->timestamp('last_message_date')->nullable();
            $table->boolean('important');
            $table->integer('customer_merge_status')->nullable();
            $table->string('ticket_user_id')->nullable();
            $table->string('sku')->nullable();
            $table->uuid('related_listing_item_id')->nullable();
            $table->foreign('related_listing_item_id')->references('id')->on('listing_item')->onDelete('cascade');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hd_ticket');
    }
};
