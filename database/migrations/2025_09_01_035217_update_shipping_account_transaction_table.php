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
            $table->dateTime('transaction_date')->collation('utf8mb4_unicode_ci')->change();
            $table->string('status_event')->after('status')->nullable();
            $table->string('status_description')->after('status_event')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_account_transaction', function (Blueprint $table) {
            $table->timestamp('transaction_date')->change();
            $table->dropColumn('status_event');
            $table->dropColumn('status_description');
        });
    }
};
