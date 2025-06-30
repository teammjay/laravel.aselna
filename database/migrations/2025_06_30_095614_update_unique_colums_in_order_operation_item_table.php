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
        Schema::table('order_operation_item', function (Blueprint $table) {
            $table->unique(['order_item_id', 'order_operation_id'], 'order_operation_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_operation_item', function (Blueprint $table) {
            $table->dropUnique('order_operation_item_unique');
        });
    }
};
