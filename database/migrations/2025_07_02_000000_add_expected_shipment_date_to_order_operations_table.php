<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpectedShipmentDateToOrderOperationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_operation', function (Blueprint $table) {
            $table->dateTime('expected_shipment_date')->nullable()->after('expected_delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_operation', function (Blueprint $table) {
            $table->dropColumn('expected_shipment_date');
        });
    }
}
