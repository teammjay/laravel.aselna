<?php

namespace App\Console\Commands\Purge;

use App\Models\Store;
use Illuminate\Support\Facades\DB;

/**
 * Shared helpers for the pre-sync purge commands.
 *
 * See docs/superpowers/plans/2026-09-17-pre-sync-purge-plan.md for the reasoning behind the
 * staging tables and the order the deletes have to run in.
 */
trait InteractsWithPurgeTables
{
    public const TABLE_STORE_PRODUCT_IDS = 'purge_store_product_ids';

    public const TABLE_LISTING_ITEM_IDS = 'purge_listing_item_ids';

    public const TABLE_ORDER_ITEM_LINKS = 'purge_order_item_links';

    /**
     * Resolve a Shopify store from a name or an id.
     */
    protected function resolveStore(string $store): ?Store
    {
        return Store::select('store.*')
            ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
            ->where('store_type.code', 'shopify')
            ->where(function ($query) use ($store) {
                $query->where('store.id', $store)
                    ->orWhere('store.name', $store);
            })
            ->first();
    }

    /**
     * Create the staging tables if they are not there yet.
     *
     * These are permanent tables on purpose: a TEMPORARY table dies with the connection, and
     * MySQL cannot open the same temporary table twice in one statement.
     */
    protected function ensurePurgeTables(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS ' . self::TABLE_STORE_PRODUCT_IDS . ' (
            store_product_id CHAR(36) NOT NULL,
            store_id         CHAR(36) NOT NULL,
            PRIMARY KEY (store_product_id),
            KEY idx_store (store_id)
        ) ENGINE=InnoDB');

        DB::statement('CREATE TABLE IF NOT EXISTS ' . self::TABLE_LISTING_ITEM_IDS . ' (
            listing_item_id CHAR(36) NOT NULL,
            store_id        CHAR(36) NOT NULL,
            batch_no        INT      NOT NULL DEFAULT 0,
            PRIMARY KEY (listing_item_id),
            KEY idx_store_batch (store_id, batch_no)
        ) ENGINE=InnoDB');

        DB::statement('CREATE TABLE IF NOT EXISTS ' . self::TABLE_ORDER_ITEM_LINKS . ' (
            order_item_id   CHAR(36) NOT NULL,
            listing_item_id CHAR(36) NOT NULL,
            store_id        CHAR(36) NOT NULL,
            PRIMARY KEY (order_item_id),
            KEY idx_listing (listing_item_id),
            KEY idx_store (store_id)
        ) ENGINE=InnoDB');
    }

    protected function purgeTablesExist(): bool
    {
        foreach ([self::TABLE_STORE_PRODUCT_IDS, self::TABLE_LISTING_ITEM_IDS, self::TABLE_ORDER_ITEM_LINKS] as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Rows still present for this store, per table. All four must be 0 after a purge.
     */
    protected function remainingCounts(string $storeId): array
    {
        return [
            'store_product' => DB::table('store_product')->where('store_id', $storeId)->count(),
            'listing_item' => DB::table('listing_item as li')
                ->join(self::TABLE_LISTING_ITEM_IDS . ' as p', 'p.listing_item_id', '=', 'li.id')
                ->where('p.store_id', $storeId)->count(),
            'listing_item_detail' => DB::table('listing_item_detail as d')
                ->join(self::TABLE_LISTING_ITEM_IDS . ' as p', 'p.listing_item_id', '=', 'd.listing_item_id')
                ->where('p.store_id', $storeId)->count(),
            'listing_item_product_images' => DB::table('listing_item_product_images as i')
                ->join(self::TABLE_LISTING_ITEM_IDS . ' as p', 'p.listing_item_id', '=', 'i.listing_item_id')
                ->where('p.store_id', $storeId)->count(),
        ];
    }

    /**
     * Global order totals, used to prove the cascade never fired.
     */
    protected function orderTotals(): array
    {
        return [
            'order_item' => DB::table('order_item')->count(),
            'order_item_attribute' => DB::table('order_item_attribute')->count(),
            'order_operation_item' => DB::table('order_operation_item')->count(),
        ];
    }
}
