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
        Schema::table('product_channel_detail', function (Blueprint $table) {
            $table->dropColumn('channel_id');
            $table->uuid('store_type_id')->nullable()->after('id');
            $table->foreign('store_type_id')->references('id')->on('store_type')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_channel_detail', function (Blueprint $table) {
            $table->dropForeign(['store_type_id']);
            $table->dropColumn('store_type_id');
        });
    }
};
