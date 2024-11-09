<?php

namespace App\Console\Commands;

use App\Jobs\BrandSyncJob;
use App\Jobs\DevicesSyncJob;
use App\Jobs\DeviceTypesSyncJob;
use Illuminate\Console\Command;

class SyncAllTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-all-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will start All the commands to sync all the tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DeviceTypesSyncJob::dispatch();
        BrandSyncJob::dispatch();
        DevicesSyncJob::dispatch();
    }
}
