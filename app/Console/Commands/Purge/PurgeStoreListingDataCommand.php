<?php

namespace App\Console\Commands\Purge;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Implements docs/superpowers/plans/2026-09-17-pre-sync-purge-plan.md sections 3 to 5.
 *
 * Deliberately not wrapped in a single transaction: the detail delete can run to ~766k rows, and
 * the staging tables plus batch_no make the run restartable without one.
 */
class PurgeStoreListingDataCommand extends Command
{
    use InteractsWithPurgeTables;

    protected $signature = 'oydera:purge
                            {store : Store name or store id (Shopify stores only)}
                            {--dry-run : Build the staging tables and report, delete nothing}
                            {--batch=500 : Listing items per batch for the listing_item_detail delete}
                            {--keep-store-product : Purge listings and below, leave store_product alone}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Purge store_product, listing_item, listing_item_detail and listing item images for one Shopify store, before an oydera re-sync.';

    public function handle()
    {
        $store = $this->resolveStore($this->argument('store'));

        if (!$store) {
            $this->error('No Shopify store found matching: ' . $this->argument('store'));

            return self::FAILURE;
        }

        $storeId = $store->id;
        $batchSize = max(1, (int) $this->option('batch'));

        $this->info('Store: ' . $store->name . ' (' . $storeId . ')');

        $this->ensurePurgeTables();

        // --- Stage (plan section 3) -------------------------------------------------------
        $this->line('');
        $this->info('Staging ids...');
        $storeProducts = $this->stageStoreProductIds($storeId);
        $listingItems = $this->stageListingItemIds($storeId, $batchSize);

        $scope = $this->scopeCounts($storeId);

        $this->line('');
        $this->table(['Table', 'Rows to delete'], [
            ['store_product', $this->option('keep-store-product') ? '0 (kept)' : number_format($storeProducts)],
            ['listing_item', number_format($listingItems)],
            ['listing_item_detail', number_format($scope['details'])],
            ['listing_item_product_images', number_format($scope['images'])],
            ['listing_item_link', number_format($scope['links'])],
            ['listing_item_history', number_format($scope['history'])],
        ]);

        // --- Preflight (plan section 1) ---------------------------------------------------
        $this->line('');
        $this->info('Order lines that would cascade-delete if left linked:');
        $this->table(['Table', 'Rows'], [
            ['order_item', number_format($scope['order_items'])],
            ['order_item_attribute', number_format($scope['order_item_attributes'])],
            ['order_operation_item', number_format($scope['order_operation_items'])],
        ]);
        $this->line('These are parked (set NULL) before the delete and restored by oydera:purge-relink.');

        if ($scope['tickets'] > 0) {
            $this->error('ABORT: ' . number_format($scope['tickets']) . ' hd_ticket rows link to these listing items.');
            $this->error('hd_ticket.related_listing_item_id cascades, and order.hd_ticket_id cascades from it,');
            $this->error('so this delete would remove whole orders and helpdesk threads. Resolve first.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line('');
            $this->info('Dry run. Nothing was deleted. Staging tables are populated and can be inspected.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Delete the rows listed above for ' . $store->name . '?', false)) {
            $this->line('Aborted. Staging tables were left in place.');

            return self::SUCCESS;
        }

        $ordersBefore = $this->orderTotals();

        // --- Park the order links (plan section 4) ----------------------------------------
        $this->line('');
        $this->info('Parking order item links...');
        $parked = $this->parkOrderItemLinks($storeId);
        $this->line('  parked and set NULL: ' . number_format($parked));

        // --- Delete (plan section 5) ------------------------------------------------------
        $this->line('');
        $this->info('Deleting (children first)...');

        $images = DB::delete('DELETE i FROM listing_item_product_images i
            JOIN ' . self::TABLE_LISTING_ITEM_IDS . ' p ON p.listing_item_id = i.listing_item_id
            WHERE p.store_id = ?', [$storeId]);
        $this->line('  listing_item_product_images: ' . number_format($images));

        $details = $this->deleteDetailsInBatches($storeId);
        $this->line('  listing_item_detail: ' . number_format($details));

        $links = DB::delete('DELETE l FROM listing_item_link l
            JOIN ' . self::TABLE_LISTING_ITEM_IDS . ' p ON p.listing_item_id = l.item_id
            WHERE p.store_id = ?', [$storeId]);
        $this->line('  listing_item_link: ' . number_format($links));

        $history = DB::delete('DELETE h FROM listing_item_history h
            JOIN ' . self::TABLE_LISTING_ITEM_IDS . ' p ON p.listing_item_id = h.item_id
            WHERE p.store_id = ?', [$storeId]);
        $this->line('  listing_item_history: ' . number_format($history));

        $items = DB::delete('DELETE li FROM listing_item li
            JOIN ' . self::TABLE_LISTING_ITEM_IDS . ' p ON p.listing_item_id = li.id
            WHERE p.store_id = ?', [$storeId]);
        $this->line('  listing_item: ' . number_format($items));

        $products = 0;
        if ($this->option('keep-store-product')) {
            $this->line('  store_product: skipped (--keep-store-product)');
        } else {
            $products = DB::delete('DELETE sp FROM store_product sp
                JOIN ' . self::TABLE_STORE_PRODUCT_IDS . ' p ON p.store_product_id = sp.id
                WHERE p.store_id = ?', [$storeId]);
            $this->line('  store_product: ' . number_format($products));
        }

        Log::info('oydera:purge completed for ' . $store->name, compact(
            'storeId', 'images', 'details', 'links', 'history', 'items', 'products', 'parked'
        ));

        // --- Verify (plan section 5) ------------------------------------------------------
        return $this->verify($storeId, $ordersBefore);
    }

    private function stageStoreProductIds(string $storeId): int
    {
        DB::table(self::TABLE_STORE_PRODUCT_IDS)->where('store_id', $storeId)->delete();

        DB::insert('INSERT INTO ' . self::TABLE_STORE_PRODUCT_IDS . ' (store_product_id, store_id)
            SELECT id, store_id FROM store_product WHERE store_id = ?', [$storeId]);

        return DB::table(self::TABLE_STORE_PRODUCT_IDS)->where('store_id', $storeId)->count();
    }

    private function stageListingItemIds(string $storeId, int $batchSize): int
    {
        DB::table(self::TABLE_LISTING_ITEM_IDS)->where('store_id', $storeId)->delete();

        $batch = 0;

        DB::table('listing_item')
            ->join(self::TABLE_STORE_PRODUCT_IDS . ' as p', 'p.store_product_id', '=', 'listing_item.store_product_id')
            ->where('p.store_id', $storeId)
            ->orderBy('listing_item.id')
            ->select('listing_item.id')
            ->chunk($batchSize, function ($rows) use (&$batch, $storeId) {
                $insert = [];

                foreach ($rows as $row) {
                    $insert[] = [
                        'listing_item_id' => $row->id,
                        'store_id' => $storeId,
                        'batch_no' => $batch,
                    ];
                }

                DB::table(self::TABLE_LISTING_ITEM_IDS)->insert($insert);
                $batch++;
            });

        return DB::table(self::TABLE_LISTING_ITEM_IDS)->where('store_id', $storeId)->count();
    }

    private function scopeCounts(string $storeId): array
    {
        $staged = self::TABLE_LISTING_ITEM_IDS . ' as p';

        $orderItemIds = DB::table('order_item as oi')
            ->join($staged, 'p.listing_item_id', '=', 'oi.listing_item_id')
            ->where('p.store_id', $storeId)
            ->select('oi.id');

        return [
            'details' => DB::table('listing_item_detail as d')
                ->join($staged, 'p.listing_item_id', '=', 'd.listing_item_id')
                ->where('p.store_id', $storeId)->count(),
            'images' => DB::table('listing_item_product_images as i')
                ->join($staged, 'p.listing_item_id', '=', 'i.listing_item_id')
                ->where('p.store_id', $storeId)->count(),
            'links' => DB::table('listing_item_link as l')
                ->join($staged, 'p.listing_item_id', '=', 'l.item_id')
                ->where('p.store_id', $storeId)->count(),
            'history' => DB::table('listing_item_history as h')
                ->join($staged, 'p.listing_item_id', '=', 'h.item_id')
                ->where('p.store_id', $storeId)->count(),
            'order_items' => (clone $orderItemIds)->count(),
            'order_item_attributes' => DB::table('order_item_attribute')
                ->whereIn('order_item_id', (clone $orderItemIds))->count(),
            'order_operation_items' => DB::table('order_operation_item')
                ->whereIn('order_item_id', (clone $orderItemIds))->count(),
            'tickets' => DB::table('hd_ticket as t')
                ->join($staged, 'p.listing_item_id', '=', 't.related_listing_item_id')
                ->where('p.store_id', $storeId)->count(),
        ];
    }

    private function parkOrderItemLinks(string $storeId): int
    {
        DB::table(self::TABLE_ORDER_ITEM_LINKS)->where('store_id', $storeId)->delete();

        DB::insert('INSERT INTO ' . self::TABLE_ORDER_ITEM_LINKS . ' (order_item_id, listing_item_id, store_id)
            SELECT oi.id, oi.listing_item_id, ?
            FROM order_item oi
            JOIN ' . self::TABLE_LISTING_ITEM_IDS . ' p ON p.listing_item_id = oi.listing_item_id
            WHERE p.store_id = ?', [$storeId, $storeId]);

        return DB::update('UPDATE order_item oi
            JOIN ' . self::TABLE_ORDER_ITEM_LINKS . ' l ON l.order_item_id = oi.id
            SET oi.listing_item_id = NULL
            WHERE l.store_id = ?', [$storeId]);
    }

    private function deleteDetailsInBatches(string $storeId): int
    {
        $maxBatch = (int) DB::table(self::TABLE_LISTING_ITEM_IDS)->where('store_id', $storeId)->max('batch_no');
        $deleted = 0;

        $bar = $this->output->createProgressBar($maxBatch + 1);
        $bar->start();

        for ($batch = 0; $batch <= $maxBatch; $batch++) {
            $deleted += DB::delete('DELETE d FROM listing_item_detail d
                JOIN ' . self::TABLE_LISTING_ITEM_IDS . ' p ON p.listing_item_id = d.listing_item_id
                WHERE p.store_id = ? AND p.batch_no = ?', [$storeId, $batch]);

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        return $deleted;
    }

    private function verify(string $storeId, array $ordersBefore): int
    {
        $remaining = $this->remainingCounts($storeId);
        $ordersAfter = $this->orderTotals();

        $this->line('');
        $this->info('Verification');

        $rows = [];
        foreach ($remaining as $table => $count) {
            $rows[] = [$table, number_format($count), $count === 0 ? 'OK' : 'NOT EMPTY'];
        }
        $this->table(['Table', 'Rows left', 'Result'], $rows);

        $orderRows = [];
        foreach ($ordersBefore as $table => $before) {
            $after = $ordersAfter[$table];
            $orderRows[] = [$table, number_format($before), number_format($after), $before === $after ? 'OK' : 'CHANGED'];
        }
        $this->table(['Order table', 'Before', 'After', 'Result'], $orderRows);

        $failed = count(array_filter($remaining)) > 0 || $ordersBefore != $ordersAfter;

        if ($failed) {
            $this->error('Verification failed. Do not run the sync until this is understood.');

            return self::FAILURE;
        }

        $this->info('Purge complete. Next: php artisan oydera:master-switch ' . $storeId);
        $this->info('Then restore the order links: php artisan oydera:purge-relink ' . $storeId);

        return self::SUCCESS;
    }
}
