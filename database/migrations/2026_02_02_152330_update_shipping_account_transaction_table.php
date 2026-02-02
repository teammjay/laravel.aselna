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
        Schema::table('shipping_account_transaction', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_account_transaction', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'failed'])
                ->nullable()
                ->comment('Transaction status: pending, completed, or failed')->change();
        });
    }
};
