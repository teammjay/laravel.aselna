<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * listing_item_link only had a PRIMARY key, so every
     * `WHERE listing_id = ?` / `WHERE item_id IN (...)` write did a full
     * table scan and InnoDB locked every scanned row, causing
     * "1205 Lock wait timeout exceeded" between concurrent jobs.
     */
    public function up(): void
    {
        Schema::table('listing_item_link', function (Blueprint $table) {
            $table->index('listing_id', 'listing_item_link_listing_id_index');
            $table->index('item_id', 'listing_item_link_item_id_index');
            $table->index('feed_id', 'listing_item_link_feed_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('listing_item_link', function (Blueprint $table) {
            $table->dropIndex('listing_item_link_listing_id_index');
            $table->dropIndex('listing_item_link_item_id_index');
            $table->dropIndex('listing_item_link_feed_id_index');
        });
    }
};
