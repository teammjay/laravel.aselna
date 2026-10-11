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
        // Number of files the meta feed is split into. A product always lands in file
        // md5(product code) mod meta_feed_file_count, so changing it moves products between files.
        // Empty means the default of 40.
        Schema::table('store', function (Blueprint $table) {
            $table->unsignedTinyInteger('meta_feed_file_count')->nullable()->after('feed_file_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store', function (Blueprint $table) {
            $table->dropColumn('meta_feed_file_count');
        });
    }
};
