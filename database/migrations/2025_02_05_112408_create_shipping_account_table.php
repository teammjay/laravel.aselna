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
        Schema::create('shipping_account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('user_id')->nullable();
            $table->string('password')->nullable();
            $table->string('api_key')->nullable();
            $table->uuid('client');
            $table->foreign('client')->references('id')->on('client')->onDelete('cascade');
            $table->uuid('shipping_solution_id')->nullable();
//            $table->foreign('shipping_solution_id')->references('id')->on('shipping_solution')->onDelete('cascade');
            $table->string('account_id')->nullable();
            $table->string('pass_phrase')->nullable();
            $table->string('requester_id')->nullable();
            $table->uuid('dazzle_client_id')->nullable();
//            $table->foreign('dazzle_client_id')->references('id')->on('printer_client')->onDelete('cascade');
            $table->boolean('sandbox_account');
            $table->double('maximum_allowable_balance');
            $table->boolean('auto_recharge');
            $table->double('auto_recharge_threshold');
            $table->string('created_by')->nullable();
            $table->string('update_by')->nullable();
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
        Schema::dropIfExists('shipping_account');
    }
};
