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
        Schema::table('order_operation', function (Blueprint $table) {
            // add is_merged column to order_operation table
            $table->boolean('is_merged')->default(false)->after('previous_operation_type')
                ->comment('Indicates if the order operation is merged with another operation');
            // add merged_with column to order_operation table
            $table->string('merged_with')->nullable()->after('is_merged')
                ->comment('ID of the order operation with which this operation is merged');
            // add orderIds
            $table->json('orderIds')->nullable()->after('merged_with')
                ->comment('List of order IDs associated with this operation, used for merged operations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_operation', function (Blueprint $table) {
            // drop is_merged column from order_operation table
            $table->dropColumn('is_merged');
            // drop merged_with column from order_operation table
            $table->dropColumn('merged_with');
            // drop orderIds column from order_operation table
            $table->dropColumn('orderIds');
        });
    }
};
