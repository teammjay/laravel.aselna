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
        Schema::table('hd_ticket', function (Blueprint $table) {
            if (!Schema::hasColumn('hd_ticket', 'related_order_id')) {
                $table->uuid('related_order_id')->nullable()->after('status');
            }
            //customer_id
            if (!Schema::hasColumn('hd_ticket', 'customer_id')) {
                $table->uuid('customer_id')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hd_ticket', function (Blueprint $table) {
            $table->dropColumn('related_order_id');
            $table->dropColumn('customer_id');
        });
    }
};
