<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('customer_id');
            $table->string('address_line_3')->nullable()->after('address_line_2');
            $table->string('phone')->nullable()->after('address_line_3');
            $table->string('email')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn('full_name');
            $table->dropColumn('address_line_3');
            $table->dropColumn('phone');
            $table->dropColumn('email');
        });
    }
};
