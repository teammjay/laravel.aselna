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
        Schema::table('device_interface',function (Blueprint $table){
            $table->unique(['device_id','interface_id'],'device_interface_unique_idx');
        });

        Schema::table('product_interface', function (Blueprint $table){
            $table->unique(['product_id','interface_id'],'product_interface_unique_idx');
        });

        Schema::table('device_carrier', function (Blueprint $table){
            $table->unique(['device_id','carrier_id'],'device_carrier_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_interface',function (Blueprint $table){
            $table->dropUnique('device_interface_unique_idx');
        });

        Schema::table('product_interface', function (Blueprint $table){
            $table->dropUnique('product_interface_unique_idx');
        });

        Schema::table('device_carrier', function (Blueprint $table){
            $table->dropUnique('device_carrier_unique_idx');
        });
    }
};
