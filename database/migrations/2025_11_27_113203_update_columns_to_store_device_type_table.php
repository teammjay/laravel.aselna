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
        Schema::table('store_device_type', function (Blueprint $table) {
            //purpose
            $table->string('purpose')->nullable()->after('device_type_id');

            //add unique for all there columns
            $table->unique(['store_id', 'device_type_id', 'purpose'], 'store_device_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_device_type', function (Blueprint $table) {
            $table->dropColumn('purpose');
            $table->dropUnique('store_device_type_unique');
        });
    }
};
