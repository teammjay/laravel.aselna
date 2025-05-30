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
        Schema::table('shipping_setting_carrier_account', function (Blueprint $table) {
            $table->dropColumn('shipping_setting_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_setting_carrier_account', function (Blueprint $table) {
            $table->uuid('shipping_setting_id');
        });
    }
};
