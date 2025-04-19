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
        Schema::table('products', function (Blueprint $table) {
            $table->float('output_current_value')->nullable();
            $table->string('output_current_unit')->nullable();
            $table->float('input_voltage_value')->nullable();
            $table->string('input_voltage_unit')->nullable();
            $table->float('output_voltage_value')->nullable();
            $table->string('output_voltage_unit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('output_current_unit');
            $table->dropColumn('output_current_value');
            $table->dropColumn('input_voltage_value');
            $table->dropColumn('input_voltage_unit');
            $table->dropColumn('output_voltage_value');
            $table->dropColumn('output_voltage_unit');
        });
    }
};
