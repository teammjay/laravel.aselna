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
        Schema::create('account_transaction_synchronization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipping_account_id');
            $table->foreign('shipping_account_id')->references('id')->on('shipping_account')->onDelete('cascade');
            $table->dateTime('filter_start_date')->nullable();
            $table->dateTime('filter_end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transaction_synchronization');
    }
};
