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
            $table->uuid('store_id')->change();
            $table->foreign('store_id')->references('store_id')->on('store')
                ->onDelete('cascade');
            $table->uuid('device_type_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
