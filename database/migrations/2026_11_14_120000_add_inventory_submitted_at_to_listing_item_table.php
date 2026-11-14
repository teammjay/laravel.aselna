<?php

use App\Constants\FeedStatus;
use App\Constants\FeedTypes;
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
        // When an inventory feed carrying this item was last sent to the marketplace. Walmart sends
        // inventory per item as soon as the create feed reports it SUCCESS, and the catch-up job
        // (Walmart\PendingInventorySyncJob) picks up live items where this is still empty.
        Schema::table('listing_item', function (Blueprint $table) {
            $table->timestamp('inventory_submitted_at')->nullable()->after('submit_count');
        });

        // Items in listings that already had an inventory feed sent are treated as done, so the
        // catch-up job only sends inventory for items that never got any.
        DB::table('listing_item')
            ->join('listing_item_link', 'listing_item_link.item_id', '=', 'listing_item.id')
            ->whereNotNull('listing_item.store_sku')
            ->where('listing_item.store_sku', '!=', '')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('listing_feeds')
                    ->whereColumn('listing_feeds.listing_id', 'listing_item_link.listing_id')
                    ->where('listing_feeds.feed_type', FeedTypes::INVENTORY)
                    ->whereIn('listing_feeds.status', [FeedStatus::PROCESSED, FeedStatus::PROCESSING, FeedStatus::SUBMITTED]);
            })
            ->update(['listing_item.inventory_submitted_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_item', function (Blueprint $table) {
            $table->dropColumn('inventory_submitted_at');
        });
    }
};
