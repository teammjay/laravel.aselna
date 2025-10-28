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
            $table->uuid('store_id')->nullable()->after('shipping_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_account_transaction', function (Blueprint $table) {
            $table->dropColumn('store_id');
        });
    }
};
