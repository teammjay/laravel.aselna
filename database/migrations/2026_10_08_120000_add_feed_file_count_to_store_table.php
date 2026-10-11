<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Number of files the shopping feed is split into. A product always lands in file
        // md5(product code) mod feed_file_count, as in oydera, so changing it moves products
        // between files. Empty means oydera's default of 6.
        Schema::table('store', function (Blueprint $table) {
            $table->unsignedTinyInteger('feed_file_count')->nullable()->after('include_upc_in_feed');
        });

        // UZID's oydera feed was generated with 7 files
        DB::table('store')->where('id', 'e4424bb0-87f7-ce9e-90c3-6d9f491bc726')->update(['feed_file_count' => 7]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store', function (Blueprint $table) {
            $table->dropColumn('feed_file_count');
        });
    }
};
