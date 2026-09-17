<?php

namespace App\Console\Commands\Purge;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Implements docs/superpowers/plans/2026-09-17-pre-sync-purge-plan.md section 4 (restore half).
 *
 * Run this AFTER oydera:master-switch has repopulated the store. The sync upserts on oydera's
 * own ids, so listing items come back with the same primary key and the parked links still fit.
 */
class PurgeRelinkOrderItemsCommand extends Command
{
    use InteractsWithPurgeTables;

    protected $signature = 'oydera:purge-relink
                            {store : Store name or store id (Shopify stores only)}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Restore the order_item -> listing_item links parked by oydera:purge, after the re-sync has run.';

    public function handle()
    {
        if (!$this->purgeTablesExist()) {
            $this->error('Purge staging tables do not exist. Nothing to restore.');

            return self::FAILURE;
        }

        $store = $this->resolveStore($this->argument('store'));

        if (!$store) {
            $this->error('No Shopify store found matching: ' . $this->argument('store'));

            return self::FAILURE;
        }

        $storeId = $store->id;

        $parked = DB::table(self::TABLE_ORDER_ITEM_LINKS)->where('store_id', $storeId)->count();

        if ($parked === 0) {
            $this->info('No parked links for ' . $store->name . '. Nothing to do.');

            return self::SUCCESS;
        }

        $restorable = DB::table(self::TABLE_ORDER_ITEM_LINKS . ' as l')
            ->join('listing_item as li', 'li.id', '=', 'l.listing_item_id')
            ->where('l.store_id', $storeId)
            ->count();

        $this->info('Store: ' . $store->name . ' (' . $storeId . ')');
        $this->table(['', 'Rows'], [
            ['parked links', number_format($parked)],
            ['listing items back after sync', number_format($restorable)],
            ['not coming back', number_format($parked - $restorable)],
        ]);

        if ($parked - $restorable > 0) {
            $this->warn('Links with no listing item stay NULL. The order line keeps its data either way.');
        }

        if (!$this->option('force') && !$this->confirm('Restore ' . number_format($restorable) . ' links?', true)) {
            $this->line('Aborted.');

            return self::SUCCESS;
        }

        $restored = DB::update('UPDATE order_item oi
            JOIN ' . self::TABLE_ORDER_ITEM_LINKS . ' l ON l.order_item_id = oi.id
            JOIN listing_item li ON li.id = l.listing_item_id
            SET oi.listing_item_id = l.listing_item_id
            WHERE l.store_id = ?', [$storeId]);

        $this->info('Restored: ' . number_format($restored));

        $stillNull = DB::table(self::TABLE_ORDER_ITEM_LINKS . ' as l')
            ->join('order_item as oi', 'oi.id', '=', 'l.order_item_id')
            ->where('l.store_id', $storeId)
            ->whereNull('oi.listing_item_id')
            ->count();

        if ($stillNull > 0) {
            $this->warn('Still NULL: ' . number_format($stillNull) . ' (listing item not returned by the sync)');
        }

        Log::info('oydera:purge-relink completed for ' . $store->name, compact('storeId', 'restored', 'stillNull'));

        $this->info('Once you are satisfied, drop the staging tables: php artisan oydera:purge-teardown');

        return self::SUCCESS;
    }
}
