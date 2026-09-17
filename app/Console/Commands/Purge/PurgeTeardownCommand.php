<?php

namespace App\Console\Commands\Purge;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Implements docs/superpowers/plans/2026-09-17-pre-sync-purge-plan.md section 6.
 */
class PurgeTeardownCommand extends Command
{
    use InteractsWithPurgeTables;

    protected $signature = 'oydera:purge-teardown
                            {store? : Clear only this store rows; omit to drop the staging tables entirely}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Drop the purge staging tables, or clear one store rows from them.';

    public function handle()
    {
        if (!$this->purgeTablesExist()) {
            $this->info('Purge staging tables do not exist. Nothing to do.');

            return self::SUCCESS;
        }

        $tables = [self::TABLE_STORE_PRODUCT_IDS, self::TABLE_LISTING_ITEM_IDS, self::TABLE_ORDER_ITEM_LINKS];

        if ($storeArg = $this->argument('store')) {
            $store = $this->resolveStore($storeArg);

            if (!$store) {
                $this->error('No Shopify store found matching: ' . $storeArg);

                return self::FAILURE;
            }

            if (!$this->option('force') && !$this->confirm('Clear staged rows for ' . $store->name . '?', false)) {
                $this->line('Aborted.');

                return self::SUCCESS;
            }

            foreach ($tables as $table) {
                $deleted = DB::table($table)->where('store_id', $store->id)->delete();
                $this->line('  ' . $table . ': cleared ' . number_format($deleted));
            }

            return self::SUCCESS;
        }

        $parked = DB::table(self::TABLE_ORDER_ITEM_LINKS)->count();

        if ($parked > 0) {
            $this->warn($parked . ' parked order links are still staged.');
            $this->warn('Dropping now loses the mapping for good. Run oydera:purge-relink first.');
        }

        if (!$this->option('force') && !$this->confirm('Drop all three purge staging tables?', false)) {
            $this->line('Aborted.');

            return self::SUCCESS;
        }

        foreach ($tables as $table) {
            DB::statement('DROP TABLE IF EXISTS ' . $table);
            $this->line('  dropped ' . $table);
        }

        return self::SUCCESS;
    }
}
