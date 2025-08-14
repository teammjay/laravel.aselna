<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_account_transaction', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'failed'])
                ->nullable()
                ->after('canceled_date')
                ->comment('Transaction status: pending, completed, or failed');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_account_transaction', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
