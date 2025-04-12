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
        Schema::create('shipping_account_purhcase', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipping_account_id');
            $table->foreign('shipping_account_id')->references('id')->on('shipping_account')->onDelete('cascade');
            $table->double('amount');
            $table->timestamp('purchase_date');
            $table->string('errors')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_account_purhcase');
    }
};
