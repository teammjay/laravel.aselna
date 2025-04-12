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
        Schema::create('shipping_account_transaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipping_account_id');
            $table->foreign('shipping_account_id')->references('id')->on('shipping_account')->onDelete('cascade');
            $table->string('tracking_number')->nullable();
            $table->timestamp('transaction_date');
            $table->string('transaction_id')->nullable();
            $table->double('amount');
            $table->string('address')->nullable();
            $table->boolean('canceled')->nullable();
            $table->timestamp('canceled_date')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_account_transaction');
    }
};
