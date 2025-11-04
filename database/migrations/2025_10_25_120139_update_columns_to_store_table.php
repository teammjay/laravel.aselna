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
        Schema::table('store', function (Blueprint $table) {
            // auto_submit_listing and re_submit_listing columns to be nullable
            $table->boolean('auto_submit_listing')->nullable();
            $table->boolean('re_submit_listing')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store', function (Blueprint $table) {
            $table->dropColumn('auto_submit_listing');
            $table->dropColumn('re_submit_listing');
        });
    }
};
